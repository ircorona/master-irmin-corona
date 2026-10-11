"""Levanta un nginx de usar y tirar (el de Laragon, con config propia) con un
WordPress mínimo detrás (php-cgi) y comprueba las cabeceras de respuesta con
peticiones reales. Sale con 0 si todo pasa.

    python prueba.py            # las comprobaciones
    python prueba.py --servir   # deja nginx arrancado: curl -I http://127.0.0.1:8022/servicios-seo/

Puertos (no toca el nginx ni el Apache de Laragon):
  8021  clase.conf       el código de clase, tal cual
  8022  ejercicio.conf   X-Mensaje, X-Robots-Tag y Link con map, sin if
  9121  php-cgi.exe de Laragon en modo FastCGI
"""
import glob
import http.client
import os
import shutil
import subprocess
import sys
import tempfile
import time

AQUI = os.path.dirname(os.path.abspath(__file__)).replace("\\", "/")
NGINX = sorted(glob.glob("C:/laragon/bin/nginx/nginx-*"))[-1].replace("\\", "/")
PHP_CGI = sorted(glob.glob("C:/laragon/bin/php/php-*/php-cgi.exe"))[-1]
FCGI = 9121
CLASE, EJ = 8021, 8022
FOTO = "/wp-content/themes/sanchezdonate/images/sobre-mi/sobre-carlos.png"
WP_LINK = '<https://mi-web.test/wp-json/>; rel="https://api.w.org/"'
CANONICAL_PDF = '<https://mi-web.test/guia-seo/>; rel="canonical"'

NGINX_CONF = """
worker_processes 1;
error_log logs/error.log warn;
pid logs/nginx.pid;
events {{ worker_connections 64; }}
http {{
    include mime.types;
    default_type application/octet-stream;
    access_log off;
    upstream php_upstream {{ server 127.0.0.1:{fcgi}; }}
    include sites/*.conf;
}}
"""

fallos = 0


def pedir(puerto, ruta):
    """Devuelve (código, {cabecera en minúsculas: [valores]}, cuerpo)."""
    c = http.client.HTTPConnection("127.0.0.1", puerto, timeout=5)
    c.request("GET", ruta)
    r = c.getresponse()
    cuerpo = r.read().decode("utf-8", "replace")
    cab = {}
    for k, v in r.getheaders():
        cab.setdefault(k.lower(), []).append(v)
    return r.status, cab, cuerpo


def ok(cond, que):
    global fallos
    fallos += not cond
    print(f"{'OK ' if cond else 'MAL'} {que}")


def caso(puerto, ruta, esperado, nota, codigo=200):
    """esperado: {cabecera: lista de valores exacta, o None = que no venga}."""
    s, cab, _ = pedir(puerto, ruta)
    bien = s == codigo and all(cab.get(k.lower()) == v for k, v in esperado.items())
    global fallos
    fallos += not bien
    print(f"{'OK ' if bien else 'MAL'} :{puerto} {ruta[:44]:44} {s}  {nota}")
    if not bien:
        for k in esperado:
            print(f"        {k}: {cab.get(k.lower())}")


def comprobar():
    A = "X-Asdrubal"
    asdrubal = ["Pagina con el SEO revisado por Asdrubal SEO SL"]
    noindex = ["noindex"]

    print("== Código de clase")
    caso(CLASE, "/", {A: asdrubal, "X-Robots-Tag": None}, "portada: X-Asdrubal")
    caso(CLASE, "/sobre-mi/", {A: asdrubal, "X-Robots-Tag": None}, "/sobre-mi/: su if está comentado, solo X-Asdrubal")
    caso(CLASE, "/servicios-seo/", {"X-Robots-Tag": noindex}, "/servicios-seo/: noindex")
    caso(CLASE, "/servicios-seo", {"X-Robots-Tag": noindex}, "sin barra también (/?)")
    caso(CLASE, "/servicios-seo-locales/", {"X-Robots-Tag": None}, "/servicios-seo-locales/ no: el $ ancla")
    caso(CLASE, "/servicios-seo/", {A: None}, "pero X-Asdrubal DESAPARECE: el if tiene su add_header")
    caso(CLASE, "/servicios-seo/?utm_source=news", {"X-Robots-Tag": None},
         "con parámetros, SIN noindex: $request_uri lleva la ?")
    caso(CLASE, FOTO, {"X-Robots-Tag": noindex, A: None}, "la foto: noindex, y X-Asdrubal desaparece")
    s, _, cuerpo = pedir(CLASE, "/servicios-seo/")
    ok("WordPress sirve: /servicios-seo/" in cuerpo, "dentro del if, WordPress sigue respondiendo (fastcgi_pass se hereda)")
    caso(CLASE, "/no-existe/", {A: None}, "un 404 de WordPress: sin always, ni X-Asdrubal", codigo=404)

    print("\n== Ejercicio")
    M = "X-Mensaje"
    msg = ["SEO revisado por Irmin Corona"]
    caso(EJ, "/", {M: msg, "X-Robots-Tag": None, "Link": [WP_LINK]},
         "portada: X-Mensaje; X-Robots-Tag y Link vacíos no se envían")
    caso(EJ, "/servicios-seo/", {M: msg, "X-Robots-Tag": noindex}, "/servicios-seo/: noindex y el mensaje sigue")
    caso(EJ, "/servicios-seo", {"X-Robots-Tag": noindex}, "sin barra")
    caso(EJ, "/servicios-seo/?utm_source=news", {"X-Robots-Tag": noindex}, "con parámetros también: (\\?|$)")
    caso(EJ, "/servicios-seo-locales/", {"X-Robots-Tag": None}, "/servicios-seo-locales/ no")
    caso(EJ, "/sobre-mi/", {M: msg, "X-Robots-Tag": None}, "/sobre-mi/: solo el mensaje")
    caso(EJ, FOTO, {M: msg, "X-Robots-Tag": noindex}, "la foto: noindex y el mensaje")
    caso(EJ, FOTO + "?v=2", {"X-Robots-Tag": noindex}, "la foto con ?v=2 también")
    caso(EJ, "/descargas/guia-seo.pdf", {M: msg, "Link": [CANONICAL_PDF], "X-Robots-Tag": None},
         "el PDF: canonical a su página HTML")
    caso(EJ, "/sobre-mi/", {"Link": [WP_LINK]}, "la Link de WordPress llega intacta")

    print("\n== Dos Link: nginx suma, no sustituye")
    s, cab, _ = pedir(EJ, "/servicios-seo/")
    ok(cab.get("link") == [WP_LINK], "en WordPress solo está la Link de la API REST (la del map va vacía)")


def comprobar_dos_link(tmp):
    """Una Link de nginx sobre una página de WordPress: salen dos cabeceras Link."""
    print("\n== Demo: add_header Link en una página de WordPress")
    ruta = f"{tmp}/conf/sites/ejercicio.conf"
    texto = open(ruta, encoding="utf-8").read()
    with open(ruta, "w", encoding="utf-8") as f:
        f.write(texto.replace('"~^/descargas/guia-seo\\.pdf(\\?|$)"', '"~^/sobre-mi/?(\\?|$)"'))
    proc = arrancar(tmp)
    try:
        s, cab, _ = pedir(EJ, "/sobre-mi/")
        ok(cab.get("link") == [WP_LINK, CANONICAL_PDF],
           f"dos cabeceras Link: la de WordPress y la de nginx ({len(cab.get('link', []))})")
    finally:
        parar(proc, tmp)


def preparar(tmp):
    for d in ("conf/sites", "logs", "temp"):
        os.makedirs(f"{tmp}/{d}", exist_ok=True)
    for f in ("mime.types", "fastcgi_params"):
        shutil.copy(f"{NGINX}/conf/{f}", f"{tmp}/conf/{f}")
    with open(f"{tmp}/conf/nginx.conf", "w", encoding="utf-8") as f:
        f.write(NGINX_CONF.format(fcgi=FCGI))
    for nombre in os.listdir(f"{AQUI}/conf"):
        texto = open(f"{AQUI}/conf/{nombre}", encoding="utf-8").read().replace("__SITIO__", f"{AQUI}/sitio")
        with open(f"{tmp}/conf/sites/{nombre}", "w", encoding="utf-8") as f:
            f.write(texto)


def arrancar(tmp):
    r = subprocess.run([f"{NGINX}/nginx.exe", "-p", f"{tmp}/", "-t"], capture_output=True, text=True)
    if r.returncode != 0:
        raise SystemExit("nginx -t falló:\n" + r.stderr)
    php = subprocess.Popen([PHP_CGI, "-b", f"127.0.0.1:{FCGI}"], env={**os.environ, "PHP_FCGI_MAX_REQUESTS": "0"})
    proc = subprocess.Popen([f"{NGINX}/nginx.exe", "-p", f"{tmp}/"], cwd=tmp)
    proc.php = php
    for _ in range(50):
        try:
            if pedir(EJ, "/")[0] == 200:
                return proc
        except OSError:
            pass
        time.sleep(0.1)
    parar(proc, tmp)
    raise SystemExit("nginx no arrancó: " + open(f"{tmp}/logs/error.log").read())


def parar(proc, tmp):
    subprocess.run([f"{NGINX}/nginx.exe", "-p", f"{tmp}/", "-s", "stop"], capture_output=True)
    for p in (proc, proc.php):
        subprocess.run(["taskkill", "/T", "/F", "/PID", str(p.pid)], capture_output=True)
        p.wait(timeout=10)
    time.sleep(0.5)


def main():
    sys.stdout.reconfigure(encoding="utf-8")
    tmp = tempfile.mkdtemp(prefix="nginx-clase18-").replace("\\", "/")
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
        comprobar_dos_link(tmp)
        print(f"\n{'Todas las comprobaciones OK' if not fallos else f'{fallos} FALLOS'}")
        return 1 if fallos else 0
    finally:
        shutil.rmtree(tmp, ignore_errors=True)


if __name__ == "__main__":
    sys.exit(main())
