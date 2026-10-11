"""Levanta un nginx de usar y tirar (el de Laragon, con config propia) y
comprueba el server de clase y el del ejercicio con peticiones reales.
Sale con 0 si todo pasa.

    python prueba.py            # las comprobaciones
    python prueba.py --servir   # deja nginx arrancado para mirar con curl

Puertos (no toca el nginx ni el Apache de Laragon):
  8061 / 8461  conf/clase.conf             el server de la foto, tal cual
  8062 / 8462  conf/ejercicio.conf         el ejercicio (http -> https)
  8063         conf/demo-add-header.conf   la trampa de add_header
  9161         php-cgi.exe de Laragon en modo FastCGI
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
import warnings

# Las pruebas de TLS 1.0/1.1 usan versiones que Python marca como obsoletas
warnings.filterwarnings("ignore", category=DeprecationWarning)

AQUI = os.path.dirname(os.path.abspath(__file__)).replace("\\", "/")
NGINX_DIR = sorted(glob.glob("C:/laragon/bin/nginx/nginx-*"))[-1].replace("\\", "/")
PHP_CGI = sorted(glob.glob("C:/laragon/bin/php/php-*/php-cgi.exe"))[-1]
FCGI = 9161
HTTP_CLASE, HTTPS_CLASE, HTTP_EJ, HTTPS_EJ, DEMO = 8061, 8461, 8062, 8462, 8063

NGINX_CONF = """
worker_processes 1;
error_log logs/error.log warn;
pid logs/nginx.pid;
events {{ worker_connections 64; }}
http {{
    include mime.types;
    default_type application/octet-stream;
    access_log off;
    # Como el php_upstream.conf de Laragon
    upstream php_upstream {{
        server 127.0.0.1:{fcgi};
    }}
    include sites/*.conf;
}}
"""

# El snippets/fastcgi-php.conf de Debian/Ubuntu que incluye la foto de clase.
# Laragon no lo trae hasta que se cambia a nginx desde su menú.
FASTCGI_PHP = """
fastcgi_split_path_info ^(.+?\\.php)(/.*)$;
try_files $fastcgi_script_name =404;
set $path_info $fastcgi_path_info;
fastcgi_param PATH_INFO $path_info;
fastcgi_index index.php;
include fastcgi.conf;
"""

fallos = 0


def pedir(puerto, ruta, host, tls=False, cabeceras=None):
    """GET. Devuelve (código, cabeceras en minúsculas, cuerpo)."""
    if tls:
        ctx = ssl.create_default_context()
        ctx.check_hostname = False
        ctx.verify_mode = ssl.CERT_NONE
        c = http.client.HTTPSConnection("127.0.0.1", puerto, timeout=5, context=ctx)
    else:
        c = http.client.HTTPConnection("127.0.0.1", puerto, timeout=5)
    c.request("GET", ruta, headers={"Host": host, **(cabeceras or {})})
    r = c.getresponse()
    cuerpo = r.read().decode("utf-8", "replace")
    return r.status, {k.lower(): v for k, v in r.getheaders()}, cuerpo


def ok(cond, que, detalle=""):
    global fallos
    fallos += not cond
    print(f"{'OK ' if cond else 'MAL'} {que}" + (f"   [{detalle}]" if detalle else ""))


def tls_version(puerto, version):
    """¿Acepta el servidor una conexión con exactamente esa versión de TLS?"""
    ctx = ssl.SSLContext(ssl.PROTOCOL_TLS_CLIENT)
    ctx.check_hostname = False
    ctx.verify_mode = ssl.CERT_NONE
    ctx.minimum_version = ctx.maximum_version = version
    if version < ssl.TLSVersion.TLSv1_2:
        ctx.set_ciphers("ALL:@SECLEVEL=0")  # que el cliente no sea el que se niega
    try:
        with ctx.wrap_socket(socket.create_connection(("127.0.0.1", puerto), timeout=5)) as s:
            return s.version()
    except (ssl.SSLError, OSError):
        return None


def alpn(puerto):
    ctx = ssl.create_default_context()
    ctx.check_hostname = False
    ctx.verify_mode = ssl.CERT_NONE
    ctx.set_alpn_protocols(["h2", "http/1.1"])
    with ctx.wrap_socket(socket.create_connection(("127.0.0.1", puerto), timeout=5),
                         server_hostname="mi-web.test") as s:
        return s.selected_alpn_protocol()


def comprobar_clase():
    h = "sanchezdonate.test"
    print("== El server de clase, tal cual")
    s, c, b = pedir(HTTP_CLASE, "/", h)
    ok(s == 200 and "INICIO" in b, "la web funciona (index.php por FastCGI)")
    s, c, b = pedir(HTTP_CLASE, "/viejo", h)
    ok("x-desde-htaccess" not in c and s == 200, "el .htaccess no se lee: ni redirección ni cabecera",
       f"{s}, sale el index.php")
    s, c, b = pedir(HTTP_CLASE, "/no-existe/", h)
    ok(s == 200 and "INICIO" in b, "una URL que no existe da 200 con la portada (soft 404)", str(s))
    s, c, b = pedir(HTTP_CLASE, "/sub/", h)
    ok(s == 200 and "Index of" in b and "informe-interno.txt" in b, "autoindex on: lista la carpeta", str(s))
    s, c, b = pedir(HTTP_CLASE, "/.htaccess", h)
    ok(s == 403, "/.htaccess: 403 (location ~ /\\.ht)", str(s))
    s, c, b = pedir(HTTP_CLASE, "/.env", h)
    ok(s == 200 and "DB_PASSWORD" in b, "/.env: 200 con la contraseña dentro", str(s))
    s, c, b = pedir(HTTP_CLASE, "/", h)
    s2, c2, b2 = pedir(HTTPS_CLASE, "/", h, tls=True)
    ok(s == 200 and s2 == 200, "http y https dan las dos 200: web duplicada, sin redirección")
    v = tls_version(HTTPS_CLASE, ssl.TLSVersion.TLSv1)
    ok(v is None, "TLSv1 en ssl_protocols, pero OpenSSL 3 lo rechaza igual", str(v))
    ok(alpn(HTTPS_CLASE) == "http/1.1", "sin http2: la conexión se queda en HTTP/1.1")


def comprobar_ejercicio():
    h = "mi-web.test"
    print("\n== Ejercicio")
    s, c, _ = pedir(HTTP_EJ, "/pagina?x=1", h)
    ok(s == 301 and c.get("location") == f"https://{h}:{HTTPS_EJ}/pagina?x=1", "http -> https con 301", c.get("location"))
    ok(alpn(HTTPS_EJ) == "h2", "HTTP/2 (http2 on)")
    ok(tls_version(HTTPS_EJ, ssl.TLSVersion.TLSv1_1) is None and tls_version(HTTPS_EJ, ssl.TLSVersion.TLSv1_3) == "TLSv1.3",
       "TLS 1.3 sí, TLS 1.1 no (valores por defecto)")
    s, c, b = pedir(HTTPS_EJ, "/", h, tls=True)
    ok(s == 200 and "INICIO" in b and c.get("x-content-type-options") == "nosniff", "portada")
    s, _, _ = pedir(HTTPS_EJ, "/no-existe/", h, tls=True)
    ok(s == 404, "URL que no existe: 404 de verdad", str(s))
    s, _, b = pedir(HTTPS_EJ, "/sub/", h, tls=True)
    ok(s == 403 and "informe-interno" not in b, "carpeta sin índice: 403, no se lista", str(s))
    for f in ("/.htaccess", "/.env"):
        s, _, _ = pedir(HTTPS_EJ, f, h, tls=True)
        ok(s == 404, f"{f}: 404", str(s))

    print("\n== Ejercicio: clases 07 a 12 en nginx")
    s, c, _ = pedir(HTTPS_EJ, "/catalogo.pdf", h, tls=True)
    ok(c.get("x-robots-tag") == "noindex" and c.get("x-content-type-options") == "nosniff",
       "07 X-Robots-Tag en el PDF (y nosniff repetido)")
    s, _, _ = pedir(HTTPS_EJ, "/", h, tls=True, cabeceras={"User-Agent": "Screaming Frog SEO Spider/21.0"})
    ok(s == 403, "08 user-agent bloqueado: 403", str(s))
    s, c, _ = pedir(HTTPS_EJ, "/estilo.css", h, tls=True, cabeceras={"Accept-Encoding": "gzip"})
    ok(c.get("content-encoding") == "gzip" and c.get("cache-control") == "max-age=31536000",
       "09 CSS con gzip y un año de caché", f"{c.get('content-encoding')} {c.get('cache-control')}")
    s, _, b = pedir(HTTPS_EJ, "/indireccion/asdrubal/", h, tls=True)
    ok(s == 200 and "CONTENIDO DE /ejemplo/" in b, "10 indirección", str(s))
    s, _, b = pedir(HTTPS_EJ, "/indireccion/asdrubal/sub/", h, tls=True)
    ok(s == 200 and "/ejemplo/sub/" in b, "10 ...y lo que cuelga", str(s))
    s, c, _ = pedir(HTTPS_EJ, "/indireccion/asdrubal", h, tls=True)
    ok(s == 301 and c.get("location", "").endswith("/indireccion/asdrubal/"), "10 sin barra: 301 a la URL pública")
    s, c, _ = pedir(HTTPS_EJ, "/indireccion/asdrubal/sub", h, tls=True)
    ok(s == 301 and c.get("location", "").endswith("/indireccion/asdrubal/sub/"),
       "10 subcarpeta sin barra: 301 a la pública, no enseña /ejemplo/sub/", c.get("location"))
    s, _, _ = pedir(HTTPS_EJ, "/indireccion/asdrubal/.env", h, tls=True)
    ok(s == 404, "10 un fichero oculto a través de la indirección: 404", str(s))
    s, _, _ = pedir(HTTPS_EJ, "/ejemplo/", h, tls=True)
    ok(s == 404, "10 /ejemplo/ desde fuera: 404 (internal)", str(s))
    s, _, _ = pedir(HTTPS_EJ, "/img/foto.jpg", h, tls=True, cabeceras={"Referer": "https://www.google.es/"})
    ok(s == 204, "11 Google Imágenes: 204", str(s))
    s, _, _ = pedir(HTTPS_EJ, "/img/foto.jpg", h, tls=True, cabeceras={"Referer": "https://otra-web.com/"})
    ok(s == 403, "11 otra web: 403", str(s))
    s, _, _ = pedir(HTTPS_EJ, "/img/foto.jpg", h, tls=True)
    ok(s == 200, "11 sin Referer (Googlebot): 200", str(s))
    s, _, _ = pedir(HTTPS_EJ, "/img/foto.jpg", h, tls=True, cabeceras={"Referer": f"https://{h}/"})
    ok(s == 200, "11 desde la propia web: 200", str(s))
    s, c, _ = pedir(HTTPS_EJ, "/notas.txt", h, tls=True)
    ok(c.get("content-disposition") == "attachment", "12 notas.txt se descarga")
    s, c, _ = pedir(HTTPS_EJ, "/robots.txt", h, tls=True)
    ok(s == 200 and "content-disposition" not in c, "12 robots.txt se ve")


def comprobar_demo():
    print("\n== La trampa de add_header")
    _, c, _ = pedir(DEMO, "/notas.txt", "demo.test")
    ok(c.get("x-content-type-options") == "nosniff", "sin add_header propio: hereda la cabecera del server")
    _, c, _ = pedir(DEMO, "/catalogo.pdf", "demo.test")
    ok(c.get("x-robots-tag") == "noindex" and "x-content-type-options" not in c,
       "con add_header propio: la del server DESAPARECE")


def preparar(tmp):
    for d in ("conf/snippets", "conf/sites", "logs", "temp"):
        os.makedirs(f"{tmp}/{d}", exist_ok=True)
    for f in ("mime.types", "fastcgi_params", "fastcgi.conf"):
        shutil.copy(f"{NGINX_DIR}/conf/{f}", f"{tmp}/conf/{f}")
    with open(f"{tmp}/conf/snippets/fastcgi-php.conf", "w", encoding="utf-8") as f:
        f.write(FASTCGI_PHP)
    with open(f"{tmp}/conf/nginx.conf", "w", encoding="utf-8") as f:
        f.write(NGINX_CONF.format(fcgi=FCGI))
    for nombre in os.listdir(f"{AQUI}/conf"):
        texto = open(f"{AQUI}/conf/{nombre}", encoding="utf-8").read().replace("__SITIO__", f"{AQUI}/sitio")
        with open(f"{tmp}/conf/sites/{nombre}", "w", encoding="utf-8") as f:
            f.write(texto)


def arrancar(tmp):
    r = subprocess.run([f"{NGINX_DIR}/nginx.exe", "-p", f"{tmp}/", "-t"], capture_output=True, text=True)
    if r.returncode != 0:
        raise SystemExit("nginx -t falló:\n" + r.stderr)
    php = subprocess.Popen([PHP_CGI, "-b", f"127.0.0.1:{FCGI}"], env={**os.environ, "PHP_FCGI_MAX_REQUESTS": "0"})
    proc = subprocess.Popen([f"{NGINX_DIR}/nginx.exe", "-p", f"{tmp}/"], cwd=tmp)
    proc.php = php
    for _ in range(50):
        try:
            pedir(DEMO, "/robots.txt", "demo.test")
            return proc
        except OSError:
            time.sleep(0.1)
    parar(proc, tmp)
    raise SystemExit("nginx no arrancó: " + open(f"{tmp}/logs/error.log").read())


def parar(proc, tmp):
    subprocess.run([f"{NGINX_DIR}/nginx.exe", "-p", f"{tmp}/", "-s", "stop"], capture_output=True)
    for p in (proc, proc.php):
        subprocess.run(["taskkill", "/T", "/F", "/PID", str(p.pid)], capture_output=True)
        p.wait(timeout=10)
    time.sleep(0.5)


def main():
    sys.stdout.reconfigure(encoding="utf-8")
    tmp = tempfile.mkdtemp(prefix="nginx-clase13-").replace("\\", "/")
    try:
        preparar(tmp)
        proc = arrancar(tmp)
        try:
            if "--servir" in sys.argv:
                print(f"nginx en :{HTTP_CLASE} :{HTTPS_CLASE} :{HTTP_EJ} :{HTTPS_EJ} :{DEMO} (tmp {tmp}). Ctrl+C para parar.")
                try:
                    while True:
                        time.sleep(1)
                except KeyboardInterrupt:
                    return 0
            comprobar_clase()
            comprobar_ejercicio()
            comprobar_demo()
        finally:
            parar(proc, tmp)
        print(f"\n{'Todas las comprobaciones OK' if not fallos else f'{fallos} FALLOS'}")
        return 1 if fallos else 0
    finally:
        shutil.rmtree(tmp, ignore_errors=True)


if __name__ == "__main__":
    sys.exit(main())
