"""Levanta un Apache de usar y tirar (el de Laragon, con config propia) y un
WordPress de pruebas con SQLite, y comprueba las indirecciones con peticiones
reales. Sale con 0 si todo pasa.

    python prueba.py                      # las comprobaciones
    python prueba.py --servir [escenario] # deja Apache arrancado para mirar con curl

Puertos (no toca el Apache ni el MySQL de Laragon):
  8091  sitio/                  el ejercicio, en código normal
  8092  demos/estandar-clase/   la indirección estándar de clase, tal cual
  8093  WordPress de pruebas    en una carpeta temporal, base de datos SQLite
  9193  php-cgi.exe de Laragon (PHP 8.3, el que trae pdo_sqlite) en modo FastCGI

WordPress y el plugin SQLite Database Integration se descargan de wordpress.org
la primera vez y se guardan en .cache/ (fuera de git).
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
HTTPD = sorted(glob.glob("C:/laragon/bin/apache/httpd-*/bin/httpd.exe"))[-1]
SERVER_ROOT = os.path.dirname(os.path.dirname(HTTPD)).replace("\\", "/")
# El PHP 8.3 de Laragon es el que trae pdo_sqlite
PHP_DIR = sorted(glob.glob("C:/laragon/bin/php/php-8.3*"))[-1].replace("\\", "/")
SITIO, CLASE, WP, FCGI = 8091, 8092, 8093, 9193
WP_URL = f"http://127.0.0.1:{WP}"
CACHE = AQUI + "/.cache"
DESCARGAS = {
    "wordpress.zip": "https://wordpress.org/latest.zip",
    "sqlite.zip": "https://downloads.wordpress.org/plugin/sqlite-database-integration.zip",
}

MODULOS = ["authz_core", "dir", "mime", "rewrite", "headers", "setenvif", "proxy", "proxy_fcgi"]

CONF = """
ServerRoot "{root}"
Listen 127.0.0.1:{sitio}
Listen 127.0.0.1:{clase}
Listen 127.0.0.1:{wp}
ServerName localhost
{modulos}
TypesConfig conf/mime.types
DirectoryIndex index.php index.html
ErrorLog "{tmp}/error.log"
LogLevel warn
PidFile "{tmp}/httpd.pid"
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
<Directory "{tmp}/wp">
    AllowOverride All
    Require all granted
</Directory>
<VirtualHost 127.0.0.1:{sitio}>
    DocumentRoot "{aqui}/sitio"
</VirtualHost>
<VirtualHost 127.0.0.1:{clase}>
    DocumentRoot "{aqui}/demos/estandar-clase"
</VirtualHost>
<VirtualHost 127.0.0.1:{wp}>
    DocumentRoot "{tmp}/wp"
</VirtualHost>
"""

HTACCESS_WP = """# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteRule ^index\\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
# END WordPress
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

# Escenarios de WordPress: (reglas extra del .htaccess antes del bloque de WP, mu-plugins)
ESCENARIOS = {
    "wp-solo": ("", []),
    "htaccess-clase": ("wp/clase/htaccess-clase.txt", []),
    "php-clase": ("", ["wp/clase/indireccion-clase.php"]),
    "htaccess-y-php-clase": ("wp/clase/htaccess-clase.txt", ["wp/clase/indireccion-clase.php"]),
    "ejercicio": ("", ["wp/ejercicio/indireccion-articulo-seo.php"]),
}


def pedir(puerto, ruta):
    """GET por HTTP/1.1 sin seguir redirecciones. Devuelve (código, Location, cuerpo)."""
    c = http.client.HTTPConnection("127.0.0.1", puerto, timeout=15)
    c.request("GET", ruta)
    r = c.getresponse()
    return r.status, r.getheader("Location"), r.read().decode("utf-8", "replace")


def php(*args):
    r = subprocess.run([PHP_DIR + "/php.exe", *args], capture_output=True, text=True)
    if r.returncode != 0 or not r.stdout.startswith("ok"):
        raise SystemExit(f"PHP falló: {r.stdout}{r.stderr}")


def preparar_wp(tmp):
    """Descomprime WordPress + SQLite en tmp/wp y lo instala."""
    os.makedirs(CACHE, exist_ok=True)
    for nombre, url in DESCARGAS.items():
        if not os.path.exists(f"{CACHE}/{nombre}"):
            print(f"descargando {url}")
            urllib.request.urlretrieve(url, f"{CACHE}/{nombre}")
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


def escenario(wp, nombre):
    """Pone el .htaccess y los mu-plugins del escenario y regenera las reglas de WP."""
    extra, plugins = ESCENARIOS[nombre]
    reglas = open(f"{AQUI}/{extra}", encoding="utf-8").read() + "\n" if extra else ""
    with open(f"{wp}/.htaccess", "w", encoding="utf-8") as f:
        f.write(reglas + HTACCESS_WP)
    mu = f"{wp}/wp-content/mu-plugins"
    for f in os.listdir(mu):
        os.remove(f"{mu}/{f}")
    for p in plugins:
        shutil.copy(f"{AQUI}/{p}", mu)
    php(AQUI + "/wp/instalar.php", wp, WP_URL, "reglas")


def arrancar(tmp):
    conf = os.path.join(tmp, "httpd.conf")
    lineas = "\n".join(f"LoadModule {m}_module modules/mod_{m}.so" for m in MODULOS)
    with open(conf, "w", encoding="utf-8") as f:
        f.write(CONF.format(root=SERVER_ROOT, tmp=tmp, aqui=AQUI, sitio=SITIO, clase=CLASE, wp=WP,
                            fcgi=FCGI, modulos=lineas))
    # php-cgi en modo FastCGI; PHP_FCGI_MAX_REQUESTS=0 para que no se cierre a las 500
    proc_php = subprocess.Popen([PHP_DIR + "/php-cgi.exe", "-b", f"127.0.0.1:{FCGI}"],
                                env={**os.environ, "PHP_FCGI_MAX_REQUESTS": "0"})
    proc = subprocess.Popen([HTTPD, "-f", conf], stdout=subprocess.PIPE, stderr=subprocess.STDOUT)
    proc.php = proc_php
    for _ in range(50):
        if proc.poll() is not None:
            break
        try:
            pedir(SITIO, "/")
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


fallos = 0


def ok(cond, que, detalle=""):
    global fallos
    fallos += not cond
    print(f"{'OK ' if cond else 'MAL'} {que}" + (f"   [{detalle}]" if detalle else ""))


def caso(puerto, ruta, codigo, destino=None, texto=None, nota=""):
    """Una petición: código esperado, Location esperada (ruta) y texto en el cuerpo."""
    status, loc, cuerpo = pedir(puerto, ruta)
    loc = loc.replace(f"http://127.0.0.1:{puerto}", "") if loc else None
    bien = status == codigo and (destino is None or loc == destino) and (texto is None or texto in cuerpo)
    ok(bien, f"{ruta:34} {nota}", f"{status}" + (f" -> {loc}" if loc else ""))


def canonical(puerto, ruta):
    _, _, cuerpo = pedir(puerto, ruta)
    i = cuerpo.find('rel="canonical" href="')
    return cuerpo[i + 22:cuerpo.find('"', i + 22)].replace(f"http://127.0.0.1:{puerto}", "") if i >= 0 else None


def comprobar_estandar():
    print("== Ejercicio en código normal (sitio/.htaccess)")
    caso(SITIO, "/indireccion/asdrubal/", 200, texto="CONTENIDO DE /ejemplo/", nota="la indirección")
    caso(SITIO, "/indireccion/asdrubal/sub/pagina.html", 200, texto="/ejemplo/sub/pagina.html", nota="y lo que cuelga")
    caso(SITIO, "/indireccion/asdrubal", 301, "/indireccion/asdrubal/", nota="sin barra: 301 a la URL pública")
    caso(SITIO, "/indireccion/asdrubal/sub", 301, "/indireccion/asdrubal/sub/", nota="subcarpeta sin barra: igual")
    caso(SITIO, "/indireccion/asdrubalXYZ", 404, nota="no es la indirección")
    caso(SITIO, "/indireccion/asdrubals-de-uso/", 404, nota="no crea indirecciones de más")
    caso(SITIO, "/ejemplo/", 404, nota="la URL interna: 404")
    caso(SITIO, "/ejemplo", 404, nota="sin barra: 404")
    caso(SITIO, "/ejemplo/sub/pagina.html", 404, nota="lo que cuelga: 404")
    caso(SITIO, "/ejemplo/?x=1", 404, nota="con parámetros: 404")
    caso(SITIO, "/ejemplos-de-uso/", 200, texto="OTRA SECCION", nota="otra sección que empieza igual: intacta")

    print("\n== Código de clase: indirección estándar")
    caso(CLASE, "/indireccion/asdrubal/", 200, texto="CONTENIDO DE /ejemplo/", nota="la indirección funciona")
    caso(CLASE, "/ejemplo/", 404, nota="la URL interna da 404")
    caso(CLASE, "/indireccion/asdrubal", 301, "/ejemplo/", nota="sin barra: mod_dir enseña /ejemplo/ (y es 404)")
    caso(CLASE, "/indireccion/asdrubal/sub", 301, "/ejemplo/sub/", nota="subcarpeta sin barra: lo mismo")
    caso(CLASE, "/indireccion/asdrubals-de-uso/", 200, texto="OTRA SECCION", nota="asdrubal(.*) sirve otra sección")
    caso(CLASE, "/ejemplos-de-uso/", 404, nota="el 404 sin anclar tumba /ejemplos-de-uso/")


def comprobar_wp(wp):
    print("\n== Código de clase en WordPress: solo el .htaccess")
    escenario(wp, "htaccess-clase")
    caso(WP, "/articulo-seo/mi-post/", 404, nota="WordPress lee la URL original: 404")
    caso(WP, "/articulo/mi-post/", 301, "/articulo-seo/mi-post/", nota="y el post redirige a ese 404")

    print("\n== Código de clase en WordPress: solo el PHP")
    escenario(wp, "php-clase")
    caso(WP, "/articulo-seo/mi-post/", 200, texto="CONTENIDO DEL POST", nota="la indirección funciona")
    caso(WP, "/articulo/mi-post/", 200, texto="CONTENIDO DEL POST", nota="la original también: duplicado")
    c = canonical(WP, "/articulo-seo/mi-post/")
    ok(c == "/articulo/mi-post/", "canonical de /articulo-seo/ apunta a /articulo/", c)

    print("\n== Código de clase en WordPress: .htaccess + PHP")
    escenario(wp, "htaccess-y-php-clase")
    caso(WP, "/articulo/mi-post/", 301, "/articulo-seo/mi-post/", nota="la original redirige a la indirecta")
    caso(WP, "/articulo-seo/mi-post/", 200, texto="CONTENIDO DEL POST", nota="la indirecta funciona")
    c = canonical(WP, "/articulo-seo/mi-post/")
    ok(c == "/articulo/mi-post/", "...pero su canonical apunta a la URL que redirige", c)
    _, _, portada = pedir(WP, "/")
    ok("/articulo/mi-post/" in portada, "y los enlaces de la portada siguen siendo /articulo/ (301)")

    print("\n== Ejercicio en WordPress (wp/ejercicio/)")
    escenario(wp, "ejercicio")
    caso(WP, "/articulo-seo/mi-post/", 200, texto="CONTENIDO DEL POST", nota="la indirección")
    c = canonical(WP, "/articulo-seo/mi-post/")
    ok(c == "/articulo-seo/mi-post/", "canonical: la URL pública", c)
    caso(WP, "/articulo/mi-post/", 301, "/articulo-seo/mi-post/", nota="la interna: 301 a la pública")
    caso(WP, "/articulo-seo/mi-post", 301, "/articulo-seo/mi-post/", nota="sin barra: 301")
    caso(WP, "/?p=1", 301, "/articulo-seo/hello-world/", nota="?p=1: 301 a la pública")
    caso(WP, "/articulo-seo/no-existe/", 404, nota="post que no existe: 404")
    caso(WP, "/articulo-seo/mi-post/otra/", 404, nota="un segmento de más: 404")
    caso(WP, "/sample-page/", 200, nota="las páginas no cambian")
    _, _, portada = pedir(WP, "/")
    ok("/articulo-seo/mi-post/" in portada and "/articulo/mi-post/" not in portada, "enlaces de la portada")
    _, _, mapa = pedir(WP, "/wp-sitemap-posts-post-1.xml")
    ok("/articulo-seo/mi-post/" in mapa and "/articulo/" not in mapa.replace("/articulo-seo/", ""),
       "sitemap de WordPress")
    _, _, feed = pedir(WP, "/feed/")
    ok("<link>http://127.0.0.1:8093/articulo-seo/mi-post/</link>" in feed, "feed RSS")


def main():
    sys.stdout.reconfigure(encoding="utf-8")
    tmp = tempfile.mkdtemp(prefix="httpd-clase10-").replace("\\", "/")
    try:
        wp = preparar_wp(tmp)
        proc = arrancar(tmp)
        try:
            if "--servir" in sys.argv:
                nombre = sys.argv[-1] if sys.argv[-1] in ESCENARIOS else "wp-solo"
                escenario(wp, nombre)
                print(f"Apache en :{SITIO} :{CLASE} :{WP} (WP con '{nombre}', tmp {tmp}). Ctrl+C para parar.")
                try:
                    while True:
                        time.sleep(1)
                except KeyboardInterrupt:
                    return 0
            comprobar_estandar()
            comprobar_wp(wp)
        finally:
            parar(proc)
        print(f"\n{'Todas las comprobaciones OK' if not fallos else f'{fallos} FALLOS'}")
        return 1 if fallos else 0
    finally:
        shutil.rmtree(tmp, ignore_errors=True)


if __name__ == "__main__":
    sys.exit(main())
