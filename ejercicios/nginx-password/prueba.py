"""Levanta un nginx de usar y tirar (el de Laragon, con config propia) y
comprueba la protección por contraseña con peticiones reales. Sale con 0 si
todo pasa.

    python prueba.py            # las comprobaciones
    python prueba.py --servir   # deja nginx arrancado para probar en el navegador

Los .htpasswd se generan en cada ejecución con el htpasswd.exe de Apache que
trae Laragon: el repo no guarda ninguna contraseña.

Puertos (no toca el nginx ni el Apache de Laragon):
  8031  clase-server.conf          el código de clase, tal cual (rutas con \\)
  8032  clase-server-barras.conf   el mismo con rutas /
  8033  clase-location.conf        la versión del artículo: location /privado
  8034  ejercicio.conf             el ejercicio
  9131  php-cgi.exe de Laragon en modo FastCGI
"""
import base64
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
HTPASSWD = sorted(glob.glob("C:/laragon/bin/apache/httpd-*/bin/htpasswd.exe"))[-1]
FCGI = 9131
CLASE, BARRAS, LOCATION, EJ = 8031, 8032, 8033, 8034
PASS_CLASE = f"{AQUI}/sitio/pass/.htpasswd"

# usuario -> (opción de htpasswd, contraseña)
USUARIOS = {
    "irmin": ("-m", "clave-apr1"),       # MD5 de Apache ($apr1$)
    "bcrypt": ("-B", "clave-bcrypt"),    # bcrypt ($2y$), el de la clase 08
    "sha": ("-s", "clave-sha"),          # {SHA}, SHA-1 sin sal
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

fallos = 0


def pedir(puerto, ruta, cred=None):
    c = http.client.HTTPConnection("127.0.0.1", puerto, timeout=5)
    h = {}
    if cred:
        h["Authorization"] = "Basic " + base64.b64encode(":".join(cred).encode()).decode()
    c.request("GET", ruta, headers=h)
    r = c.getresponse()
    return r.status, {k.lower(): v for k, v in r.getheaders()}, r.read().decode("utf-8", "replace")


def caso(puerto, ruta, codigo, nota, cred=None, texto=None):
    global fallos
    s, h, b = pedir(puerto, ruta, cred)
    bien = s == codigo and (texto is None or texto in b)
    fallos += not bien
    quien = cred[0] if cred else "-"
    print(f"{'OK ' if bien else 'MAL'} :{puerto} {ruta:24} {quien:7} {s}   {nota}")
    return s, h, b


def ok(cond, que):
    global fallos
    fallos += not cond
    print(f"{'OK ' if cond else 'MAL'} {que}")


def bien(usuario):
    return (usuario, USUARIOS[usuario][1])


def comprobar(tmp):
    print("== Clase, tal cual: ruta de Windows con barras invertidas")
    caso(CLASE, "/", 500, "con la contraseña buena: 500", cred=bien("irmin"))
    error = open(f"{tmp}/logs/error.log", encoding="utf-8", errors="replace").read()
    ok("ejercicios\nginx-password" in error and "CreateFile()" in error,
       "el log: el \\n de \\nginx-password se ha leído como un salto de línea y la ruta no existe")

    print("\n== Clase con barras /: toda la web protegida")
    _, h, _ = caso(BARRAS, "/", 401, "sin contraseña: 401")
    ok(h.get("www-authenticate") == 'Basic realm="Administrator\'s Area"', "WWW-Authenticate con el AuthName")
    caso(BARRAS, "/", 401, "contraseña mala: 401", cred=("irmin", "otra"))
    caso(BARRAS, "/", 200, "apr1 ($apr1$): entra", cred=bien("irmin"), texto="INICIO PUBLICO")
    caso(BARRAS, "/", 401, "bcrypt ($2y$) con la clave BUENA: 401, el nginx de Windows no lo entiende",
         cred=bien("bcrypt"))
    error = open(f"{tmp}/logs/error.log", encoding="utf-8", errors="replace").read()
    ok('user "bcrypt": password mismatch' in error, "y el log dice «password mismatch», como si la clave fuera mala")
    caso(BARRAS, "/", 200, "{SHA}: entra (pero es SHA-1 sin sal)", cred=bien("sha"))
    caso(BARRAS, "/pass/", 200, "pass/index.php enseña la ruta del .htpasswd a quien entre",
         cred=bien("irmin"), texto=".htpasswd")
    caso(BARRAS, "/pass/.htpasswd", 403, "el .htpasswd: 403 por el location ~ /\\.ht", cred=bien("irmin"))

    print("\n== Artículo: location /privado en un server con PHP")
    caso(LOCATION, "/privado/informe.html", 401, "el HTML: protegido")
    caso(LOCATION, "/privado/index.php", 200, "el PHP: ABIERTO, la regex de PHP gana a location /privado",
         texto="ZONA PRIVADA")
    caso(LOCATION, "/privado/", 401, "la carpeta: protegida (el index.php se pide después, por dentro)")
    caso(LOCATION, "/privadoXYZ/informe.html", 401, "location /privado sin barra también protege /privadoXYZ")
    caso(LOCATION, "/pass/.htpasswd", 200, "el .htpasswd se DESCARGA: sin location ~ /\\.ht nginx lo sirve",
         texto="$apr1$")

    print("\n== Ejercicio")
    caso(EJ, "/", 200, "la portada sigue pública", texto="INICIO PUBLICO")
    caso(EJ, "/privado/", 401, "zona privada sin contraseña: 401")
    caso(EJ, "/privado/index.php", 401, "el PHP también pide contraseña")
    caso(EJ, "/privado/informe.html", 401, "el HTML también")
    caso(EJ, "/privado/index.php", 401, "contraseña mala: 401", cred=("irmin", "otra"))
    caso(EJ, "/privado/", 200, "contraseña buena: entra, y PHP ve el usuario",
         cred=bien("irmin"), texto="ZONA PRIVADA: irmin")
    caso(EJ, "/privado/informe.html", 200, "", cred=bien("irmin"), texto="INFORME PRIVADO")
    _, h, _ = caso(EJ, "/privado", 301, "sin barra: 301 a /privado/")
    caso(EJ, "/privadoXYZ/informe.html", 404, "/privadoXYZ no es la zona privada")
    caso(EJ, "/pass/.htpasswd", 404, "ficheros ocultos: 404")
    ok(not os.path.exists(f"{AQUI}/sitio/.htpasswd-ejercicio"), "el .htpasswd del ejercicio está fuera de la web")


def generar(destino, usuarios):
    """Crea un .htpasswd con htpasswd.exe: -c la primera vez, -b clave en la orden."""
    os.makedirs(os.path.dirname(destino), exist_ok=True)
    if os.path.exists(destino):
        os.remove(destino)
    for i, u in enumerate(usuarios):
        opcion, clave = USUARIOS[u]
        flags = ("-c" if i == 0 else "") + "b" + opcion[1]
        subprocess.run([HTPASSWD, "-" + flags.lstrip("-"), destino, u, clave], check=True, capture_output=True)


def preparar(tmp):
    for d in ("conf/sites", "logs", "temp"):
        os.makedirs(f"{tmp}/{d}", exist_ok=True)
    for f in ("mime.types", "fastcgi_params"):
        shutil.copy(f"{NGINX}/conf/{f}", f"{tmp}/conf/{f}")
    with open(f"{tmp}/conf/nginx.conf", "w", encoding="utf-8") as f:
        f.write(NGINX_CONF.format(fcgi=FCGI))
    generar(PASS_CLASE, ["irmin", "bcrypt", "sha"])            # dentro de la web, como en clase
    fuera = f"{tmp}/pass-fuera/.htpasswd"
    generar(fuera, ["irmin"])                                  # fuera de la web
    for nombre in os.listdir(f"{AQUI}/conf"):
        texto = (open(f"{AQUI}/conf/{nombre}", encoding="utf-8").read()
                 .replace("__SITIO__", f"{AQUI}/sitio")
                 .replace("__PASS_WIN__", PASS_CLASE.replace("/", "\\"))
                 .replace("__PASS_FUERA__", fuera)
                 .replace("__PASS__", PASS_CLASE))
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
    tmp = tempfile.mkdtemp(prefix="nginx-clase16-").replace("\\", "/")
    try:
        preparar(tmp)
        proc = arrancar(tmp)
        try:
            if "--servir" in sys.argv:
                print(f"nginx en :{CLASE}-:{EJ} (tmp {tmp}). Usuario irmin / {USUARIOS['irmin'][1]}. Ctrl+C para parar.")
                try:
                    while True:
                        time.sleep(1)
                except KeyboardInterrupt:
                    return 0
            comprobar(tmp)
        finally:
            parar(proc, tmp)
        print(f"\n{'Todas las comprobaciones OK' if not fallos else f'{fallos} FALLOS'}")
        return 1 if fallos else 0
    finally:
        shutil.rmtree(tmp, ignore_errors=True)
        if os.path.exists(PASS_CLASE):
            os.remove(PASS_CLASE)


if __name__ == "__main__":
    sys.exit(main())
