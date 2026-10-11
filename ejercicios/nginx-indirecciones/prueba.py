"""Levanta un nginx de usar y tirar (el de Laragon, con config propia), un
php-cgi y un WordPress con SQLite, y comprueba las indirecciones con
peticiones reales. Sale con 0 si todo pasa.

    python prueba.py                       # las comprobaciones
    python prueba.py --servir [escenario]  # deja nginx y WordPress arrancados

Usa el PHP 8.3 de Laragon (el que trae pdo_sqlite). No toca el nginx, el
Apache ni el MySQL de Laragon. La primera vez descarga WordPress y el plugin
SQLite Database Integration a .cache/ (fuera de git), o los copia de
../indirecciones/.cache/ si ya están.

Puertos:
  8006  conf/clase.conf      indirección básica + wpo.php con permanent
  8010  conf/clase.conf      wpo.php con last
  8007  conf/ejercicio.conf  la indirección en código normal
  8008  WordPress, con las reglas de cada escenario (wp/)
  9108  php-cgi.exe en modo FastCGI
"""
import glob
import http.client
import os
import shutil
import subprocess
import sys
import tempfile
import time
import urllib.request
import zipfile

AQUI = os.path.dirname(os.path.abspath(__file__)).replace("\\", "/")
NGINX = sorted(glob.glob("C:/laragon/bin/nginx/nginx-*"))[-1].replace("\\", "/")
PHP_DIR = sorted(glob.glob("C:/laragon/bin/php/php-8.3*"))[-1].replace("\\", "/")
CACHE = f"{AQUI}/.cache"
CLASE, CLASE_LAST, EJ, WP, FCGI = 8006, 8010, 8007, 8008, 9108
WP_URL = f"http://127.0.0.1:{WP}"
DESCARGAS = {
    "wordpress.zip": "https://wordpress.org/latest.zip",
    "sqlite.zip": "https://downloads.wordpress.org/plugin/sqlite-database-integration.zip",
}

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

WP_CONFIG = """<?php
define('DB_NAME', 'wp');
define('DB_USER', 'x');
define('DB_PASSWORD', 'x');
define('DB_HOST', 'localhost');
define('DB_DIR', __DIR__ . '/wp-content/database/');
define('DB_FILE', 'wp.sqlite');
define('DISABLE_WP_CRON', true);
define('WP_DEBUG', false);
$table_prefix = 'wp_';
if (!defined('ABSPATH')) define('ABSPATH', __DIR__ . '/');
require_once ABSPATH . 'wp-settings.php';
"""

# Escenarios de WordPress: (reglas de nginx, mu-plugins)
ESCENARIOS = {
    "basica": ("wp/clase/basica.conf", []),
    "exitosa-sin-php": ("wp/clase/exitosa.conf", []),
    "exitosa-con-php": ("wp/clase/exitosa.conf", ["wp/clase/indireccion-clase.php"]),
    "ejercicio": ("", ["wp/ejercicio/indireccion-articulo-seo.php"]),
}

fallos = 0


def pedir(puerto, ruta):
    """GET sin seguir redirecciones. Devuelve (código, Location sin host, cuerpo)."""
    c = http.client.HTTPConnection("127.0.0.1", puerto, timeout=15)
    c.request("GET", ruta)
    r = c.getresponse()
    loc = r.getheader("Location")
    loc = loc.replace(f"http://127.0.0.1:{puerto}", "") if loc else None
    return r.status, loc, r.read().decode("utf-8", "replace")


def ok(cond, que, detalle=""):
    global fallos
    fallos += not cond
    print(f"{'OK ' if cond else 'MAL'} {que}" + (f"   [{detalle}]" if detalle and not cond else ""))


def caso(puerto, ruta, codigo, destino=None, texto=None, nota=""):
    s, loc, cuerpo = pedir(puerto, ruta)
    bien = s == codigo and (destino is None or loc == destino) and (texto is None or texto in cuerpo)
    global fallos
    fallos += not bien
    flecha = f" -> {loc}" if loc else ""
    print(f"{'OK ' if bien else 'MAL'} :{puerto} {ruta:32} {s}{flecha:28} {nota}")


def canonical(puerto, ruta):
    _, _, cuerpo = pedir(puerto, ruta)
    i = cuerpo.find('rel="canonical" href="')
    return cuerpo[i + 22:cuerpo.find('"', i + 22)].replace(f"http://127.0.0.1:{puerto}", "") if i >= 0 else None


def comprobar_estatico():
    print("== Clase: indirección básica (sitio estático)")
    caso(CLASE, "/articulo-seo/mi-post/", 200, texto="ARTICULO mi-post", nota="la indirección funciona")
    caso(CLASE, "/articulo/mi-post/", 200, texto="ARTICULO mi-post", nota="la interna también da 200: DUPLICADO")
    caso(CLASE, "/articulo-seo/mi-post", 301, "/articulo/mi-post/",
         nota="sin barra: nginx redirige a la URL INTERNA")

    print("\n== Clase: proyecto PHP")
    caso(CLASE, "/wpo.php", 301, "/sobre-mi.php", nota="permanent: es una redirección, no una indirección")
    caso(CLASE, "/wpoXphp", 301, "/sobre-mi.php", nota="el . sin escapar casa con cualquier carácter")
    caso(CLASE_LAST, "/wpo.php", 200, texto="PAGINA sobre-mi.php", nota="last: indirección, sirve sobre-mi.php")
    caso(CLASE_LAST, "/sobre-mi.php", 200, texto="PAGINA sobre-mi.php", nota="y sobre-mi.php sigue en 200: DUPLICADO")

    print("\n== Ejercicio: indirección en código normal")
    caso(EJ, "/indireccion/asdrubal/", 200, texto="EJEMPLO portada", nota="la indirección")
    caso(EJ, "/indireccion/asdrubal/sub/", 200, texto="EJEMPLO sub", nota="una subcarpeta")
    caso(EJ, "/indireccion/asdrubal/sub/?utm_source=x", 200, texto="EJEMPLO sub", nota="con parámetros")
    caso(EJ, "/indireccion/asdrubal/sub", 301, "/indireccion/asdrubal/sub/", nota="sin barra: 301 a la PÚBLICA")
    caso(EJ, "/indireccion/asdrubal", 301, "/indireccion/asdrubal/", nota="la raíz sin barra: 301 a la pública")
    caso(EJ, "/indireccion/asdrubal/no-existe/", 404, nota="lo que no existe: 404")
    caso(EJ, "/ejemplo/", 404, nota="la interna pedida directamente: 404 (internal)")
    caso(EJ, "/ejemplo/sub/", 404, nota="también sus subcarpetas")
    caso(EJ, "/ejemplo/sub/index.html", 404, nota="y sus ficheros")
    caso(EJ, "/ejemplo", 404, nota="y sin barra")
    caso(EJ, "/ejemplos-de-uso/", 200, texto="EJEMPLOS DE USO", nota="/ejemplos-de-uso/ no es la interna: 200")
    caso(EJ, "/indireccion/asdrubals/", 404, nota="/indireccion/asdrubals/ no es la pública")


def comprobar_wp(tmp, wp):
    print("\n== WordPress + indirección básica de clase (sin PHP)")
    escenario(tmp, wp, "basica")
    caso(WP, "/articulo-seo/mi-post/", 404, nota="WordPress lee la URL ORIGINAL: 404")
    caso(WP, "/articulo/mi-post/", 200, texto="CONTENIDO DEL POST", nota="la interna sigue en 200")

    print("\n== WordPress + indirección «exitosa» de clase, sin el PHP")
    escenario(tmp, wp, "exitosa-sin-php")
    caso(WP, "/articulo/mi-post/", 301, "/articulo-seo/mi-post/", nota="el post redirige...")
    caso(WP, "/articulo-seo/mi-post/", 404, nota="...a un 404: sin el PHP WordPress no conoce la URL")

    print("\n== WordPress + indirección «exitosa» + PHP de clase")
    escenario(tmp, wp, "exitosa-con-php")
    caso(WP, "/articulo/mi-post/", 301, "/articulo-seo/mi-post/", nota="la interna redirige a la pública")
    caso(WP, "/articulo-seo/mi-post/", 200, texto="CONTENIDO DEL POST", nota="la pública funciona")
    c = canonical(WP, "/articulo-seo/mi-post/")
    ok(c == "/articulo/mi-post/", f"canonical de /articulo-seo/mi-post/ = {c}: apunta a la URL que redirige", c)
    _, _, portada = pedir(WP, "/")
    ok("/articulo/mi-post/" in portada and "/articulo-seo/mi-post/" not in portada,
       "los enlaces de la portada siguen en /articulo/ (cada clic, un 301)")
    _, _, mapa = pedir(WP, "/wp-sitemap-posts-post-1.xml")
    ok("/articulo/mi-post/" in mapa, "el sitemap también lista /articulo/ (URLs que redirigen)")
    caso(WP, "/articulo-seo/mi-post/feed/", 404, nota="(.*) mete «mi-post/feed» en name: 404")

    print("\n== Ejercicio en WordPress: sin reglas de nginx, todo en WordPress")
    escenario(tmp, wp, "ejercicio")
    caso(WP, "/articulo-seo/mi-post/", 200, texto="CONTENIDO DEL POST", nota="la indirección")
    c = canonical(WP, "/articulo-seo/mi-post/")
    ok(c == "/articulo-seo/mi-post/", "canonical: la URL pública", c)
    caso(WP, "/articulo/mi-post/", 301, "/articulo-seo/mi-post/", nota="la interna: 301 a la pública")
    caso(WP, "/articulo-seo/mi-post", 301, "/articulo-seo/mi-post/", nota="sin barra: 301")
    caso(WP, "/?p=1", 301, "/articulo-seo/hello-world/", nota="?p=1: 301 a la pública")
    caso(WP, "/articulo-seo/no-existe/", 404, nota="post que no existe: 404")
    caso(WP, "/sample-page/", 200, nota="las páginas no cambian")
    _, _, portada = pedir(WP, "/")
    ok("/articulo-seo/mi-post/" in portada and "/articulo/mi-post/" not in portada, "enlaces de la portada")
    _, _, mapa = pedir(WP, "/wp-sitemap-posts-post-1.xml")
    ok("/articulo-seo/mi-post/" in mapa and "/articulo/" not in mapa.replace("/articulo-seo/", ""), "sitemap")


def php(*args):
    r = subprocess.run([PHP_DIR + "/php.exe", *args], capture_output=True, text=True)
    if r.returncode != 0 or not r.stdout.startswith("ok"):
        raise SystemExit(f"PHP falló: {r.stdout}{r.stderr}")


def preparar_wp(tmp):
    os.makedirs(CACHE, exist_ok=True)
    for nombre, url in DESCARGAS.items():
        destino = f"{CACHE}/{nombre}"
        vecino = f"{AQUI}/../indirecciones/.cache/{nombre}"
        if not os.path.exists(destino):
            if os.path.exists(vecino):
                shutil.copy(vecino, destino)
            else:
                print(f"descargando {url}")
                urllib.request.urlretrieve(url, destino)
    zipfile.ZipFile(f"{CACHE}/wordpress.zip").extractall(tmp)
    wp = f"{tmp}/wp"
    os.rename(f"{tmp}/wordpress", wp)
    zipfile.ZipFile(f"{CACHE}/sqlite.zip").extractall(f"{wp}/wp-content/plugins")
    shutil.copy(f"{wp}/wp-content/plugins/sqlite-database-integration/db.copy", f"{wp}/wp-content/db.php")
    os.makedirs(f"{wp}/wp-content/database")
    os.makedirs(f"{wp}/wp-content/mu-plugins")
    with open(f"{wp}/wp-config.php", "w", encoding="utf-8") as f:
        f.write(WP_CONFIG)
    php(AQUI + "/wp/instalar.php", wp, WP_URL, "instalar")
    return wp


def escribir_wp_conf(tmp, wp, reglas_fichero):
    reglas = open(f"{AQUI}/{reglas_fichero}", encoding="utf-8").read() if reglas_fichero else ""
    plantilla = open(f"{AQUI}/wp/nginx-wp.conf", encoding="utf-8").read()
    with open(f"{tmp}/conf/sites/wp.conf", "w", encoding="utf-8") as f:
        f.write(plantilla.replace("__WP__", wp).replace("__REGLAS__", reglas))


def escenario(tmp, wp, nombre):
    """Pone las reglas de nginx y los mu-plugins del escenario, regenera las reglas de WP y recarga nginx."""
    reglas, plugins = ESCENARIOS[nombre]
    escribir_wp_conf(tmp, wp, reglas)
    mu = f"{wp}/wp-content/mu-plugins"
    for f in os.listdir(mu):
        os.remove(f"{mu}/{f}")
    for p in plugins:
        shutil.copy(f"{AQUI}/{p}", mu)
    php(AQUI + "/wp/instalar.php", wp, WP_URL, "reglas")
    r = subprocess.run([f"{NGINX}/nginx.exe", "-p", f"{tmp}/", "-s", "reload"], capture_output=True, text=True)
    if r.returncode != 0:
        raise SystemExit("reload falló: " + r.stderr)
    time.sleep(1)


def preparar(tmp, wp):
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
    escribir_wp_conf(tmp, wp, "")


def arrancar(tmp):
    r = subprocess.run([f"{NGINX}/nginx.exe", "-p", f"{tmp}/", "-t"], capture_output=True, text=True)
    if r.returncode != 0:
        raise SystemExit("nginx -t falló:\n" + r.stderr)
    php_proc = subprocess.Popen([PHP_DIR + "/php-cgi.exe", "-b", f"127.0.0.1:{FCGI}"],
                                env={**os.environ, "PHP_FCGI_MAX_REQUESTS": "0"})
    proc = subprocess.Popen([f"{NGINX}/nginx.exe", "-p", f"{tmp}/"], cwd=tmp)
    proc.php = php_proc
    for _ in range(50):
        try:
            pedir(EJ, "/")
            return proc
        except OSError:
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
    tmp = tempfile.mkdtemp(prefix="nginx-clase20-").replace("\\", "/")
    try:
        wp = preparar_wp(tmp)
        preparar(tmp, wp)
        proc = arrancar(tmp)
        try:
            if "--servir" in sys.argv:
                nombre = sys.argv[-1] if sys.argv[-1] in ESCENARIOS else "ejercicio"
                escenario(tmp, wp, nombre)
                print(f"nginx en :{CLASE} :{EJ} :{CLASE_LAST}, WordPress ({nombre}) en :{WP}. Ctrl+C para parar.")
                try:
                    while True:
                        time.sleep(1)
                except KeyboardInterrupt:
                    return 0
            comprobar_estatico()
            comprobar_wp(tmp, wp)
        finally:
            parar(proc, tmp)
        print(f"\n{'Todas las comprobaciones OK' if not fallos else f'{fallos} FALLOS'}")
        return 1 if fallos else 0
    finally:
        shutil.rmtree(tmp, ignore_errors=True)


if __name__ == "__main__":
    sys.exit(main())
