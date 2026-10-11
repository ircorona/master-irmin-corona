"""Levanta un nginx de usar y tirar (el de Laragon, con config propia) y
comprueba la compresión y la caché del código de clase y del ejercicio.
Además mide qué gana cada nivel de gzip. Sale con 0 si todo pasa.

    python prueba.py            # las comprobaciones
    python prueba.py --servir   # deja nginx arrancado para mirar con curl

Puertos (no toca el nginx ni el Apache de Laragon): 8041 (clase), 8042 (ejercicio)
y 8043 (clase con los tipos arreglados).
"""
import glob
import gzip
import http.client
import os
import shutil
import subprocess
import sys
import tempfile
import time
import zlib

AQUI = os.path.dirname(os.path.abspath(__file__)).replace("\\", "/")
NGINX = sorted(glob.glob("C:/laragon/bin/nginx/nginx-*"))[-1].replace("\\", "/")
CLASE, EJ, TIPOS = 8041, 8042, 8043
PRECOMPRIMIDOS = ["estilo.css", "app.js", "sitemap.xml", "index.html"]

NGINX_CONF = """
worker_processes 1;
error_log logs/error.log warn;
pid logs/nginx.pid;
events { worker_connections 64; }
http {
    include mime.types;
    default_type application/octet-stream;
    access_log off;
    include sites/*.conf;
}
"""

fallos = 0


def pedir(puerto, ruta, cabeceras=None):
    c = http.client.HTTPConnection("127.0.0.1", puerto, timeout=5)
    c.request("GET", ruta, headers={"Accept-Encoding": "gzip, br", **(cabeceras or {})})
    r = c.getresponse()
    cuerpo = r.read()
    return r.status, {k.lower(): v for k, v in r.getheaders()}, cuerpo, r.msg.get_all("Cache-Control") or []


def ok(cond, que, detalle=""):
    global fallos
    fallos += not cond
    print(f"{'OK ' if cond else 'MAL'} {que}" + (f"   [{detalle}]" if detalle else ""))


def comprimido(puerto, ruta, cabeceras=None):
    s, h, cuerpo, _ = pedir(puerto, ruta, cabeceras)
    return h.get("content-encoding"), len(cuerpo)


def comprobar():
    print("== Clase: compresión")
    for ruta, esperado, nota in (
        ("/index.html", "gzip", "HTML: siempre (text/html va de serie)"),
        ("/sitemap.xml", None, "XML: NO, nginx lo sirve como text/xml y la lista dice application/xml"),
        ("/estilo.css", None, "CSS: NO, text/css no está en gzip_types"),
        ("/app.js", None, "JS: NO, tampoco está en gzip_types"),
        ("/robots.txt", None, "robots.txt: 23 bytes, por debajo de gzip_min_length"),
    ):
        enc, n = comprimido(CLASE, ruta)
        ok(enc == esperado, f"{ruta:14} {nota}", f"{enc} {n} B")
    enc, _ = comprimido(CLASE, "/index.html", {"Via": "1.1 proxy"})
    ok(enc == "gzip", "index.html a través de un proxy (Via): comprimido (no lleva Expires)", str(enc))

    print("\n== Clase con text/css y text/xml añadidos: gzip_proxied")
    for ruta in ("/estilo.css", "/sitemap.xml"):
        enc, _ = comprimido(TIPOS, ruta)
        enc_via, _ = comprimido(TIPOS, ruta, {"Via": "1.1 proxy"})
        ok(enc == "gzip" and enc_via is None,
           f"{ruta:14} directo: gzip; por un proxy (Via): SIN comprimir (lleva Expires de caché)",
           f"{enc} / {enc_via}")

    print("\n== Clase: caché")
    for ruta, esperado, nota in (
        ("/estilo.css", "max-age=20736000", "8M = 8 x 30 días"),
        ("/fuentes/inter.woff2", "max-age=31536000", "1y"),
        ("/sitemap.xml", "max-age=1200", "20m"),
        ("/index.html", None, "HTML: sin caché definida"),
    ):
        _, h, _, cc = pedir(CLASE, ruta)
        ok(h.get("cache-control") == esperado, f"{ruta:22} {nota}", ", ".join(cc) or "ninguna")

    print("\n== Ejercicio")
    original = os.path.getsize(f"{AQUI}/sitio/estilo.css")
    for ruta in ("/index.html", "/estilo.css", "/app.js", "/sitemap.xml", "/sitemap-noticias.xml"):
        enc, n = comprimido(EJ, ruta)
        ok(enc == "gzip", f"{ruta:22} comprimido", f"{n} B")
    ok(not os.path.exists(f"{AQUI}/sitio/sitemap-noticias.xml.gz"),
       "sitemap-noticias.xml no tiene .gz: se comprime al vuelo (text/xml en la lista)")
    enc, n = comprimido(EJ, "/estilo.css")
    ok(n == os.path.getsize(f"{AQUI}/sitio/estilo.css.gz"),
       "estilo.css sale del .gz precomprimido a nivel 9 (gzip_static)", f"{original} -> {n} B")
    enc, _ = comprimido(EJ, "/index.html", {"Via": "1.1 proxy"})
    ok(enc == "gzip", "a través de un proxy (Via) también comprime", str(enc))
    enc, n = comprimido(EJ, "/estilo.css", {"Accept-Encoding": "identity"})
    ok(enc is None and n == original, "un cliente sin gzip recibe el original")
    enc, _ = comprimido(EJ, "/robots.txt")
    ok(enc is None, "robots.txt: muy pequeño, sin comprimir")
    enc, _ = comprimido(EJ, "/fuentes/inter.woff2")
    ok(enc is None, "woff2: ya viene comprimido, no se toca")
    _, h, _, cc = pedir(EJ, "/estilo.css")
    ok(cc == ["public, max-age=31536000, immutable"], "CSS: un año, immutable, una sola cabecera", ", ".join(cc))
    ok("Accept-Encoding" in h.get("vary", ""), "Vary: Accept-Encoding")
    _, h, _, cc = pedir(EJ, "/sitemap.xml")
    ok(cc == ["max-age=1200"], "sitemap.xml: 20 minutos", ", ".join(cc))
    _, h, _, cc = pedir(EJ, "/index.html")
    ok(cc == ["no-cache"], "HTML: no-cache, el navegador revalida", ", ".join(cc))
    s, h, _, _ = pedir(EJ, "/index.html")
    s2, _, cuerpo, _ = pedir(EJ, "/index.html", {"If-Modified-Since": h["last-modified"]})
    ok(s2 == 304 and not cuerpo, "revalidación: 304 sin cuerpo")


def niveles():
    """Lo que gana cada nivel de gzip con zlib, el mismo algoritmo que usa nginx."""
    print("\n== Niveles de gzip (zlib) sobre estilo.css")
    datos = open(f"{AQUI}/sitio/estilo.css", "rb").read()
    print(f"   original {len(datos)} B")
    for nivel in (1, 5, 6, 9):
        t = time.perf_counter()
        for _ in range(200):
            n = len(zlib.compress(datos, nivel))
        us = (time.perf_counter() - t) / 200 * 1e6
        print(f"   nivel {nivel}: {n:>5} B ({100 - n * 100 // len(datos)} % menos)   {us:6.0f} µs por compresión")


def comprobar_brotli(tmp):
    print("\n== Clase: bloque de Brotli en este nginx")
    with open(f"{tmp}/conf/sites/clase.conf", encoding="utf-8") as f:
        texto = f.read()
    brotli = open(f"{AQUI}/conf/brotli-clase.inc", encoding="utf-8").read()
    with open(f"{tmp}/conf/sites/clase.conf", "w", encoding="utf-8") as f:
        f.write(texto.replace("gzip on;", brotli + "    gzip on;", 1))
    r = subprocess.run([f"{NGINX}/nginx.exe", "-p", f"{tmp}/", "-t"], capture_output=True, text=True)
    ok(r.returncode != 0 and 'unknown directive "brotli"' in r.stderr,
       "«unknown directive brotli»: sin el módulo ngx_brotli, nginx no arranca")


def preparar(tmp):
    for d in ("conf/sites", "logs", "temp"):
        os.makedirs(f"{tmp}/{d}", exist_ok=True)
    shutil.copy(f"{NGINX}/conf/mime.types", f"{tmp}/conf/mime.types")
    with open(f"{tmp}/conf/nginx.conf", "w", encoding="utf-8") as f:
        f.write(NGINX_CONF)
    for nombre in ("clase.conf", "ejercicio.conf", "clase-tipos-arreglados.conf"):
        texto = open(f"{AQUI}/conf/{nombre}", encoding="utf-8").read().replace("__SITIO__", f"{AQUI}/sitio")
        with open(f"{tmp}/conf/sites/{nombre}", "w", encoding="utf-8") as f:
            f.write(texto)
    # Los precomprimidos para gzip_static, a nivel 9 (fuera de git)
    for nombre in PRECOMPRIMIDOS:
        datos = open(f"{AQUI}/sitio/{nombre}", "rb").read()
        with open(f"{AQUI}/sitio/{nombre}.gz", "wb") as f:
            f.write(gzip.compress(datos, 9, mtime=0))


def arrancar(tmp):
    r = subprocess.run([f"{NGINX}/nginx.exe", "-p", f"{tmp}/", "-t"], capture_output=True, text=True)
    if r.returncode != 0:
        raise SystemExit("nginx -t falló:\n" + r.stderr)
    proc = subprocess.Popen([f"{NGINX}/nginx.exe", "-p", f"{tmp}/"], cwd=tmp)
    for _ in range(50):
        try:
            pedir(EJ, "/robots.txt")
            return proc
        except OSError:
            time.sleep(0.1)
    parar(proc, tmp)
    raise SystemExit("nginx no arrancó: " + open(f"{tmp}/logs/error.log").read())


def parar(proc, tmp):
    subprocess.run([f"{NGINX}/nginx.exe", "-p", f"{tmp}/", "-s", "stop"], capture_output=True)
    subprocess.run(["taskkill", "/T", "/F", "/PID", str(proc.pid)], capture_output=True)
    proc.wait(timeout=10)
    time.sleep(0.5)


def main():
    sys.stdout.reconfigure(encoding="utf-8")
    tmp = tempfile.mkdtemp(prefix="nginx-clase15-").replace("\\", "/")
    try:
        preparar(tmp)
        proc = arrancar(tmp)
        try:
            if "--servir" in sys.argv:
                print(f"nginx en :{CLASE} :{EJ} (tmp {tmp}). Ctrl+C para parar.")
                try:
                    while True:
                        time.sleep(1)
                except KeyboardInterrupt:
                    return 0
            comprobar()
        finally:
            parar(proc, tmp)
        comprobar_brotli(tmp)
        niveles()
        print(f"\n{'Todas las comprobaciones OK' if not fallos else f'{fallos} FALLOS'}")
        return 1 if fallos else 0
    finally:
        shutil.rmtree(tmp, ignore_errors=True)


if __name__ == "__main__":
    sys.exit(main())
