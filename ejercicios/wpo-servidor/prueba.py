"""Levanta un Apache de usar y tirar (el de Laragon, con config propia) y
comprueba la caché de usuario, la compresión, la caché de servidor, HTTP/2 y
Early Hints con peticiones reales. Sale con 0 si todo pasa.

    python prueba.py            # las comprobaciones
    python prueba.py --servir   # deja Apache arrancado para mirar con curl

Puertos (no toca el Apache de Laragon):
  8096  sitio/  el ejercicio por HTTP/1.1
  8097  demos/  el código de clase tal cual
  8443  sitio/  por HTTPS con HTTP/2 y Early Hints (certificado de Laragon)
  9196  php-cgi.exe de Laragon en modo FastCGI

El bloque "httpd.conf" de CONF es el ejercicio extra: lo que no puede ir en
un .htaccess (caché de servidor, Protocols, H2EarlyHints).
Necesita el paquete h2 de Python para la parte de HTTP/2 (pip install h2).
"""
import glob
import http.client
import os
import shutil
import socket
import ssl
import subprocess
import sys
import tempfile
import time
from email.utils import parsedate_to_datetime

AQUI = os.path.dirname(os.path.abspath(__file__)).replace("\\", "/")
HTTPD = sorted(glob.glob("C:/laragon/bin/apache/httpd-*/bin/httpd.exe"))[-1]
SERVER_ROOT = os.path.dirname(os.path.dirname(HTTPD)).replace("\\", "/")
SSL_DIR = "C:/laragon/etc/ssl"
PHP_CGI = sorted(glob.glob("C:/laragon/bin/php/php-*/php-cgi.exe"))[-1]
SITIO, DEMOS, TLS, FCGI = 8096, 8097, 8443, 9196

MODULOS = [
    "authz_core", "authz_host", "dir", "mime", "rewrite", "headers", "setenvif",
    "expires", "filter", "deflate", "brotli", "proxy", "proxy_fcgi",
    "cache", "cache_disk", "socache_shmcb", "ssl", "http2",
]

CONF = """
ServerRoot "{root}"
Listen 127.0.0.1:{sitio}
Listen 127.0.0.1:{demos}
Listen 127.0.0.1:{tls}
ServerName localhost
{modulos}
TypesConfig conf/mime.types
DirectoryIndex index.html
ErrorLog "{tmp}/error.log"
LogLevel warn
PidFile "{tmp}/httpd.pid"
SSLSessionCache "shmcb:{tmp}/ssl_scache(512000)"
# PHP por FastCGI con el php-cgi.exe de Laragon (lo arranca el script)
<FilesMatch "\\.php$">
    SetHandler "proxy:fcgi://127.0.0.1:{fcgi}/"
    ProxyFCGISetEnvIf "true" SCRIPT_FILENAME "%{{DOCUMENT_ROOT}}%{{REQUEST_URI}}"
</FilesMatch>
<Directory "/">
    AllowOverride None
</Directory>
<Directory "{aqui}">
    AllowOverride All
    Require all granted
</Directory>

# ---- httpd.conf: lo que no cabe en un .htaccess ----------------------------
# Caché de servidor en disco (mod_cache + mod_cache_disk)
CacheRoot "{tmp}/cache"
CacheDirLevels 1
CacheDirLength 1
CacheHeader on

<VirtualHost 127.0.0.1:{sitio}>
    DocumentRoot "{aqui}/sitio"
    # Solo lo dinámico: lo estático ya es rápido de servir.
    # La duración la pone el s-maxage de sitio/dinamico/.htaccess.
    CacheEnable disk /dinamico/
</VirtualHost>

<VirtualHost 127.0.0.1:{demos}>
    DocumentRoot "{aqui}/demos"
    # El bloque de clase (CacheRoot va arriba: es de servidor)
    CacheEnable disk /
    CacheDefaultExpire 3600
    # Lo que le falta para cachear un PHP. No se admite en .htaccess (500)
    <Location "/dinamico-sin-lastmod/">
        CacheIgnoreNoLastMod On
    </Location>
</VirtualHost>

<VirtualHost 127.0.0.1:{tls}>
    DocumentRoot "{aqui}/sitio"
    SSLEngine on
    SSLCertificateFile "{ssl}/laragon.crt"
    SSLCertificateKeyFile "{ssl}/laragon.key"
    Protocols h2 http/1.1
    H2EarlyHints on
    # Server Push: Chrome lo quitó en 2022; Early Hints es su sustituto
    H2Push off
</VirtualHost>
"""

BR = {"Accept-Encoding": "gzip, deflate, br"}
GZ = {"Accept-Encoding": "gzip"}
UN_ANO, CUATRO_MESES, MESES_364 = "max-age=31536000", "max-age=10368000", "max-age=31449600"
fallos = 0


def pedir(puerto, ruta, cabeceras=None, origen="127.0.0.1"):
    """GET por HTTP/1.1. Devuelve un dict con las cabeceras en minúsculas,
    más "status" y "cuerpo"."""
    c = http.client.HTTPConnection("127.0.0.1", puerto, timeout=5, source_address=(origen, 0))
    c.request("GET", ruta, headers=cabeceras or {})
    r = c.getresponse()
    h = {k.lower(): v for k, v in r.getheaders()}
    h["status"], h["cuerpo"] = r.status, r.read()
    return h


def h2_estados(ruta):
    """GET por HTTPS con HTTP/2. Devuelve (protocolo ALPN, [códigos recibidos])."""
    import h2.config
    import h2.connection
    import h2.events
    ctx = ssl.create_default_context()
    ctx.check_hostname = False
    ctx.verify_mode = ssl.CERT_NONE  # certificado de Laragon, autofirmado
    ctx.set_alpn_protocols(["h2", "http/1.1"])
    s = ctx.wrap_socket(socket.create_connection(("127.0.0.1", TLS), timeout=5),
                        server_hostname="localhost")
    alpn = s.selected_alpn_protocol()
    c = h2.connection.H2Connection(h2.config.H2Configuration(client_side=True))
    c.initiate_connection()
    c.send_headers(1, [(":method", "GET"), (":path", ruta), (":authority", "localhost"),
                       (":scheme", "https")], end_stream=True)
    s.sendall(c.data_to_send())
    estados, fin = [], False
    while not fin:
        for e in c.receive_data(s.recv(65535)):
            if isinstance(e, (h2.events.InformationalResponseReceived, h2.events.ResponseReceived)):
                estados.append(dict(e.headers)[b":status"].decode())
            fin = fin or (isinstance(e, h2.events.StreamEnded) and e.stream_id == 1)
        s.sendall(c.data_to_send())
    s.close()
    return alpn, estados


def ok(cond, que, detalle=""):
    global fallos
    fallos += not cond
    print(f"{'OK ' if cond else 'MAL'} {que}" + (f"   [{detalle}]" if detalle else ""))


def x_cache(h):
    return h.get("x-cache", "-")[:4].strip()


def comprobar_sitio():
    print("== Ejercicio: caché de usuario")
    h = pedir(SITIO, "/")
    ok(h.get("cache-control") == "max-age=0", "HTML: el navegador revalida siempre", h.get("cache-control"))
    for ruta in ("/css/estilo.css?v=1", "/js/app.js?v=1", "/img/foto.jpg", "/favicon.ico",
                 "/fuentes/inter.woff2"):
        h = pedir(SITIO, ruta)
        ok(h.get("cache-control") == UN_ANO, f"{ruta}: un año", f"{h['content-type']}")
    h = pedir(SITIO, "/sitemap.xml")
    ok(h.get("cache-control") == "max-age=86400", "sitemap.xml: un día", h.get("cache-control"))

    print("\n== Ejercicio: compresión")
    original = os.path.getsize(AQUI + "/sitio/css/estilo.css")
    h = pedir(SITIO, "/css/estilo.css?v=1", BR)
    ok(h.get("content-encoding") == "br", "CSS con br aceptado -> Brotli",
       f"{len(h['cuerpo'])} de {original} bytes")
    h = pedir(SITIO, "/css/estilo.css?v=1", GZ)
    ok(h.get("content-encoding") == "gzip", "CSS solo con gzip -> gzip", f"{len(h['cuerpo'])} bytes")
    ok("Accept-Encoding" in h.get("vary", ""), "Vary: Accept-Encoding", h.get("vary"))
    h = pedir(SITIO, "/css/estilo.css?v=1")
    ok("content-encoding" not in h, "sin Accept-Encoding -> sin comprimir", f"{len(h['cuerpo'])} bytes")
    for ruta in ("/fuentes/inter.woff2", "/img/foto.jpg"):
        h = pedir(SITIO, ruta, BR)
        ok("content-encoding" not in h, f"{ruta}: ya viene comprimido, no se toca", h["content-type"])

    print("\n== Ejercicio: WebP en la misma URL")
    h = pedir(SITIO, "/img/foto.jpg", {"Accept": "image/avif,image/webp,*/*"})
    ok(h["content-type"] == "image/webp", "navegador con WebP -> WebP", f"{len(h['cuerpo'])} bytes")
    h = pedir(SITIO, "/img/foto.jpg", {"Accept": "image/png,*/*"})
    ok(h["content-type"] == "image/jpeg", "navegador sin WebP -> JPEG", f"{len(h['cuerpo'])} bytes")
    ok("Accept" in h.get("vary", ""), "Vary: Accept", h.get("vary"))

    print("\n== Ejercicio extra (httpd.conf): caché de servidor")
    t = time.perf_counter()
    a = pedir(SITIO, "/dinamico/hora.php")
    t1 = time.perf_counter() - t
    t = time.perf_counter()
    b = pedir(SITIO, "/dinamico/hora.php")
    t2 = time.perf_counter() - t
    ok(x_cache(a) == "MISS" and x_cache(b) == "HIT", "PHP: primera MISS, segunda HIT",
       f"{t1 * 1000:.0f} ms -> {t2 * 1000:.0f} ms")
    ok(a["cuerpo"] == b["cuerpo"], "la segunda es la misma página: no se ha generado otra vez")
    ok(b.get("cache-control") == "max-age=0, s-maxage=60",
       "el navegador revalida; mod_cache la guarda 60 s", b.get("cache-control"))

    print("\n== Ejercicio extra (httpd.conf): HTTP/2 y Early Hints")
    alpn, estados = h2_estados("/")
    ok(alpn == "h2", "HTTPS negocia HTTP/2 (ALPN)", alpn)
    ok(estados == ["103", "200"], "página: 103 Early Hints y luego 200", " ".join(estados))
    _, estados = h2_estados("/dinamico/hora.php")
    ok(estados == ["103", "200"], "PHP: el 103 sale mientras se genera", " ".join(estados))
    _, estados = h2_estados("/css/estilo.css?v=1")
    ok(estados == ["200"], "CSS: sin 103", " ".join(estados))


def comprobar_demos():
    print("\n== Código de clase: Brotli y Deflate")
    h = pedir(DEMOS, "/brotli-clase/estilo.css", BR)
    ok(h.get("content-encoding") == "br", "BROTLI_COMPRESS comprime el CSS")
    h = pedir(DEMOS, "/brotli-clase/fuente.woff", BR)
    ok(h["content-type"] == "x-font/woff", "AddType x-font/woff cambia el tipo de las .woff",
       h["content-type"])
    h = pedir(DEMOS, "/brotli-y-deflate/estilo.css", BR)
    ok(h.get("content-encoding") == "br", "bloque Brotli antes que Deflate -> br")
    h = pedir(DEMOS, "/deflate-y-brotli/estilo.css", BR)
    ok(h.get("content-encoding") == "gzip", "Deflate antes que Brotli -> gzip aunque acepte br",
       h.get("content-encoding"))

    print("\n== Código de clase: caché de usuario")
    h = pedir(DEMOS, "/expires-clase/")
    ok(h.get("cache-control") == "max-age=14400, must-revalidate", "HTML: Cache-Control dice 4 horas")
    dias = (parsedate_to_datetime(h["expires"]) - parsedate_to_datetime(h["date"])).days
    ok(dias == 120, "...y Expires dice 4 meses (ExpiresDefault)", f"{dias} días")
    h = pedir(DEMOS, "/expires-clase/fuente.woff2")
    ok(h.get("cache-control") == CUATRO_MESES,
       "woff2: 4 meses y no el año (application/font-woff2 no casa)", h["content-type"])
    h = pedir(DEMOS, "/expires-clase/favicon.ico")
    ok(h.get("cache-control") == MESES_364 + ", public",
       "favicon: manda el Header de 364 días (image/ico no casa)", h["content-type"])

    print("\n== Código de clase: caché de servidor")
    a, b = pedir(DEMOS, "/dinamico/hora.php"), pedir(DEMOS, "/dinamico/hora.php")
    ok(x_cache(b) == "MISS" and a["cuerpo"] != b["cuerpo"],
       "PHP: no se guarda nunca (no manda Last-Modified)", f"{x_cache(a)} {x_cache(b)}")
    a, b = pedir(DEMOS, "/dinamico-sin-lastmod/hora.php"), pedir(DEMOS, "/dinamico-sin-lastmod/hora.php")
    ok(x_cache(b) == "HIT" and a["cuerpo"] == b["cuerpo"], "con CacheIgnoreNoLastMod On, sí",
       f"{x_cache(a)} {x_cache(b)}")
    pedir(DEMOS, "/expires-clase/foto.jpg")
    time.sleep(0.3)  # mod_cache_disk termina de escribir el fichero tras responder
    h = pedir(DEMOS, "/expires-clase/foto.jpg")
    ok(x_cache(h) == "HIT", "lo estático sí se guarda (y no lo necesitaba)")

    print("\n== Código de clase: caché de servidor + bloqueo por IP de la clase 08")
    h = pedir(DEMOS, "/privado/informe.html", origen="127.0.0.2")
    ok(h["status"] == 403, "127.0.0.2, caché vacía: 403", str(h["status"]))
    pedir(DEMOS, "/privado/informe.html")  # entra 127.0.0.1 y la respuesta se guarda
    h = pedir(DEMOS, "/privado/informe.html", origen="127.0.0.2")
    ok(h["status"] == 200 and x_cache(h) == "HIT",
       "127.0.0.2 después: 200 desde la caché, el Require ip ni se mira", f"{h['status']} {x_cache(h)}")

    print("\n== Código de clase: WebP")
    h = pedir(DEMOS, "/webp-clase/foto.jpg", {"Accept": "image/avif,image/webp,*/*"})
    ok(h["content-type"] == "image/jpeg", "el navegador acepta WebP y sale el JPEG: falta la regla",
       h["content-type"])


def comprobar_sin_cache_disk(tmp):
    print("\n== Código de clase: bloque mod_cache sin mod_cache_disk")
    conf = os.path.join(tmp, "sin-disk.conf")
    with open(conf, "w", encoding="utf-8") as f:
        f.write(f'ServerRoot "{SERVER_ROOT}"\nListen 127.0.0.1:8099\nServerName localhost\n'
                "LoadModule cache_module modules/mod_cache.so\n"
                f'<IfModule mod_cache.c>\nCacheEnable disk /\nCacheRoot "{tmp}/cache/"\n'
                "CacheDefaultExpire 3600\n</IfModule>\n")
    r = subprocess.run([HTTPD, "-t", "-f", conf], capture_output=True, text=True)
    ok(r.returncode != 0 and "Invalid command 'CacheRoot'" in r.stderr,
       "Apache no arranca: CacheRoot es de mod_cache_disk, no de mod_cache")


def arrancar(tmp):
    os.makedirs(f"{tmp}/cache", exist_ok=True)
    conf = os.path.join(tmp, "httpd.conf")
    lineas = "\n".join(f"LoadModule {m}_module modules/mod_{m}.so" for m in MODULOS)
    with open(conf, "w", encoding="utf-8") as f:
        f.write(CONF.format(root=SERVER_ROOT, tmp=tmp, aqui=AQUI, sitio=SITIO, demos=DEMOS,
                            tls=TLS, ssl=SSL_DIR, fcgi=FCGI, modulos=lineas))
    # php-cgi en modo FastCGI; PHP_FCGI_MAX_REQUESTS=0 para que no se cierre a las 500
    php = subprocess.Popen([PHP_CGI, "-b", f"127.0.0.1:{FCGI}"],
                           env={**os.environ, "PHP_FCGI_MAX_REQUESTS": "0"})
    proc = subprocess.Popen([HTTPD, "-f", conf], stdout=subprocess.PIPE, stderr=subprocess.STDOUT)
    proc.php = php
    for _ in range(50):
        if proc.poll() is not None:
            break
        try:
            pedir(SITIO, "/robots.txt")
            return proc
        except OSError:
            time.sleep(0.1)
    parar(proc)
    raise SystemExit("Apache no arrancó: " + proc.stdout.read().decode(errors="replace"))


def parar(proc):
    # En Windows Apache lanza un proceso hijo: hay que matar el árbol entero
    for p in (proc, proc.php):
        subprocess.run(["taskkill", "/T", "/F", "/PID", str(p.pid)], capture_output=True)
        p.wait(timeout=10)
    time.sleep(0.5)


def main():
    sys.stdout.reconfigure(encoding="utf-8")
    tmp = tempfile.mkdtemp(prefix="httpd-clase09-").replace("\\", "/")
    try:
        proc = arrancar(tmp)
        try:
            if "--servir" in sys.argv:
                print(f"Apache en :{SITIO} :{DEMOS} :{TLS} (tmp {tmp}). Ctrl+C para parar.")
                try:
                    while True:
                        time.sleep(1)
                except KeyboardInterrupt:
                    return 0
            comprobar_sitio()
            comprobar_demos()
        finally:
            parar(proc)
        comprobar_sin_cache_disk(tmp)
        print(f"\n{'Todas las comprobaciones OK' if not fallos else f'{fallos} FALLOS'}")
        return 1 if fallos else 0
    finally:
        shutil.rmtree(tmp, ignore_errors=True)


if __name__ == "__main__":
    sys.exit(main())
