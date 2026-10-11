"""Levanta un Apache de usar y tirar (el de Laragon, con config propia) y
comprueba la cabecera Content-Disposition de cada fichero. Sale con 0 si todo pasa.

    python prueba.py            # las comprobaciones
    python prueba.py --servir   # deja Apache arrancado para mirar con curl

Arranca Apache dos veces: con todos los módulos, y sin mod_headers para ver
qué hace el código de clase en un servidor al que le falta.
Puertos (no toca el Apache de Laragon): 8071 (sitio/) y 8072 (demos/).
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
HTTPD = sorted(glob.glob("C:/laragon/bin/apache/httpd-*/bin/httpd.exe"))[-1]
SERVER_ROOT = os.path.dirname(os.path.dirname(HTTPD)).replace("\\", "/")
SITIO, DEMOS = 8071, 8072

MODULOS = ["authz_core", "mime", "headers"]

CONF = """
ServerRoot "{root}"
Listen 127.0.0.1:{sitio}
Listen 127.0.0.1:{demos}
ServerName localhost
{modulos}
TypesConfig conf/mime.types
ErrorLog "{tmp}/error.log"
LogLevel warn
PidFile "{tmp}/httpd.pid"
<Directory "/">
    AllowOverride None
</Directory>
<Directory "{aqui}">
    AllowOverride All
    Require all granted
</Directory>
<VirtualHost 127.0.0.1:{sitio}>
    DocumentRoot "{aqui}/sitio"
</VirtualHost>
<VirtualHost 127.0.0.1:{demos}>
    DocumentRoot "{aqui}/demos"
</VirtualHost>
"""

fallos = 0


def pedir(puerto, ruta):
    c = http.client.HTTPConnection("127.0.0.1", puerto, timeout=5)
    c.request("GET", ruta)
    r = c.getresponse()
    r.read()
    return r.status, r.getheader("Content-Disposition")


def caso(puerto, ruta, esperado, nota, codigo=200):
    """esperado: valor de Content-Disposition, o None si no debe haber cabecera."""
    global fallos
    status, cd = pedir(puerto, ruta)
    bien = status == codigo and cd == esperado
    fallos += not bien
    print(f"{'OK ' if bien else 'MAL'} :{puerto} {ruta:34} {status} {str(cd):42} {nota}")


def comprobar():
    print("== Ejercicio")
    caso(SITIO, "/notas.txt", "attachment", "un .txt cualquiera: se descarga")
    caso(SITIO, "/LEEME.TXT", "attachment", "extensión en mayúsculas: también")
    for f in ("robots.txt", "llms.txt", "ads.txt", "sitemap.txt"):
        caso(SITIO, f"/{f}", None, "fichero público: se ve en el navegador")
    caso(SITIO, "/catalogo-2026.pdf", 'attachment; filename="Catalogo 2026.pdf"', "PDF con nombre")

    print("\n== Código de clase")
    caso(DEMOS, "/clase/notas.txt", "attachment", "se descarga")
    caso(DEMOS, "/clase/robots.txt", "inline", "robots.txt se ve")
    caso(DEMOS, "/clase/sitemap.txt", "attachment", "sitemap.txt: se descarga")
    caso(DEMOS, "/clase/llms.txt", "attachment", "llms.txt: se descarga")
    caso(DEMOS, "/clase/ads.txt", "attachment", "ads.txt: se descarga")
    caso(DEMOS, "/clase/LEEME.TXT", "attachment", "LEEME.TXT: en Windows casa (en Linux no)")

    print("\n== Código de clase con los bloques al revés")
    caso(DEMOS, "/orden-invertido/robots.txt", "attachment", "robots.txt se descarga: gana el último")


def comprobar_sin_headers():
    print("\n== Sin mod_headers")
    caso(DEMOS, "/clase/robots.txt", None, "código de clase sin <IfModule>: 500 en toda la carpeta", 500)
    caso(SITIO, "/notas.txt", None, "ejercicio con <IfModule>: se sirve, sin cabecera")


def arrancar(tmp, modulos):
    conf = os.path.join(tmp, "httpd.conf")
    lineas = "\n".join(f"LoadModule {m}_module modules/mod_{m}.so" for m in modulos)
    with open(conf, "w", encoding="utf-8") as f:
        f.write(CONF.format(root=SERVER_ROOT, tmp=tmp, aqui=AQUI, sitio=SITIO, demos=DEMOS, modulos=lineas))
    proc = subprocess.Popen([HTTPD, "-f", conf], stdout=subprocess.PIPE, stderr=subprocess.STDOUT)
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
    subprocess.run(["taskkill", "/T", "/F", "/PID", str(proc.pid)], capture_output=True)
    proc.wait(timeout=10)
    time.sleep(0.5)


def main():
    sys.stdout.reconfigure(encoding="utf-8")
    tmp = tempfile.mkdtemp(prefix="httpd-clase12-").replace("\\", "/")
    try:
        proc = arrancar(tmp, MODULOS)
        try:
            if "--servir" in sys.argv:
                print(f"Apache en :{SITIO} :{DEMOS} (tmp {tmp}). Ctrl+C para parar.")
                try:
                    while True:
                        time.sleep(1)
                except KeyboardInterrupt:
                    return 0
            comprobar()
        finally:
            parar(proc)
        proc = arrancar(tmp, [m for m in MODULOS if m != "headers"])
        try:
            comprobar_sin_headers()
        finally:
            parar(proc)
        print(f"\n{'Todas las comprobaciones OK' if not fallos else f'{fallos} FALLOS'}")
        return 1 if fallos else 0
    finally:
        shutil.rmtree(tmp, ignore_errors=True)


if __name__ == "__main__":
    sys.exit(main())
