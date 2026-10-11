"""Levanta un Apache de usar y tirar (el de Laragon, con config propia) y
comprueba la protección contra el hotlinking con peticiones reales, variando
la cabecera Referer. Sale con 0 si todo pasa.

    python prueba.py            # las comprobaciones
    python prueba.py --servir   # deja Apache arrancado para mirar con curl

Puertos (no toca el Apache de Laragon):
  8081  sitio/   el ejercicio
  8082  demos/   el código de clase y el del artículo de Carlos, tal cual
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
SITIO, DEMOS = 8081, 8082
MI_WEB = "mi-web.test"
TAMANO = os.path.getsize(AQUI + "/sitio/img/foto.jpg")

MODULOS = ["authz_core", "dir", "mime", "rewrite", "headers"]

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


def pedir(puerto, ruta, referer=None, host=MI_WEB):
    """GET con el Referer indicado. Devuelve (código, bytes del cuerpo, cabeceras)."""
    c = http.client.HTTPConnection("127.0.0.1", puerto, timeout=5)
    h = {"Host": host}
    if referer:
        h["Referer"] = referer
    c.request("GET", ruta, headers=h)
    r = c.getresponse()
    return r.status, len(r.read()), {k.lower(): v for k, v in r.getheaders()}


def caso(puerto, ruta, referer, codigo, nota, host=MI_WEB):
    global fallos
    status, n, _ = pedir(puerto, ruta, referer, host)
    bien = status == codigo and (codigo != 200 or n == TAMANO) and (codigo == 200 or n == 0 or codigo == 403)
    fallos += not bien
    quien = referer or "(sin Referer)"
    print(f"{'OK ' if bien else 'MAL'} {quien:42} {status} {n:>6} B  {nota}")


def comprobar():
    img = "/img/foto.jpg"
    print("== Ejercicio: Google Imágenes (opt-out de enlaces insertados)")
    caso(SITIO, img, "https://www.google.com/", 204, "Google Imágenes: 204, solo su miniatura")
    caso(SITIO, img, "https://www.google.es/", 204, "otro dominio de Google")
    caso(SITIO, img, "https://www.google.co.uk/", 204, "dominio de dos niveles")
    caso(SITIO, img, "https://images.google.com/", 204, "subdominio images.")
    caso(SITIO, "/img/foto.webp", "https://www.google.com/", 204, "también el WebP")
    caso(SITIO, img, None, 200, "Googlebot-Image (no manda Referer): la imagen entera")
    caso(SITIO, img, "https://google.otraweb.com/", 403, "google.otraweb.com no es Google")

    print("\n== Ejercicio: hotlinking del resto")
    caso(SITIO, img, f"https://{MI_WEB}/articulo/", 200, "desde la propia web")
    caso(SITIO, img, f"http://{MI_WEB}/", 200, "propia web por http")
    caso(SITIO, img, "https://otra-web.com/robado/", 403, "otra web que inserta la imagen: 403")
    caso(SITIO, img, f"https://{MI_WEB}.otra-web.com/", 403, "dominio que empieza igual: 403")
    caso(SITIO, img, "https://www.bing.com/", 200, "Bing Imágenes: pasa")
    caso(SITIO, img, "https://duckduckgo.com/", 200, "DuckDuckGo: pasa")
    # Lo que manda el navegador con las dos páginas de demostración
    caso(SITIO, img, f"http://127.0.0.1:{SITIO}/", 200, "sitio/index.html en el navegador",
         host=f"127.0.0.1:{SITIO}")
    caso(SITIO, img, f"http://127.0.0.1:{DEMOS}/", 403, "demos/otra-web.html en el navegador",
         host=f"127.0.0.1:{SITIO}")

    print("\n== Código de clase, tal cual")
    caso(DEMOS, "/clase/img/foto.jpg", "https://www.google.com/", 204, "Google: 204")
    caso(DEMOS, "/clase/img/foto.jpg", None, 200, "sin Referer: 200")
    caso(DEMOS, "/clase/img/foto.jpg", "https://images.google.com/", 200,
         "images.google.com se escapa: (www\\.)? solo cubre www")
    caso(DEMOS, "/clase/img/foto.jpg", "https://otra-web.com/", 200, "otras webs: no las toca")

    print("\n== «Prevenir Hotlinking» del artículo, tal cual")
    art = "/articulo-hotlinking/img/foto.jpg"
    caso(DEMOS, art, "https://carlos.sanchezdonate.com/post/", 200, "desde su antiguo dominio: 200")
    caso(DEMOS, art, None, 204, "SIN Referer: 204, también para Googlebot-Image")
    caso(DEMOS, art, "https://sanchezdonate.net/articulo/", 204, "desde su dominio actual: 204")
    caso(DEMOS, art, "https://carlosXsanchezdonate.com/", 200, "el punto sin escapar deja pasar a otro")
    status, n, h = pedir(DEMOS, art)
    global fallos
    bien = status == 204 and n == 0 and "content-type" not in h
    fallos += not bien
    print(f"{'OK ' if bien else 'MAL'} ErrorDocument 204 con HTML: Apache no envía cuerpo ({n} B)")


def arrancar(tmp):
    conf = os.path.join(tmp, "httpd.conf")
    lineas = "\n".join(f"LoadModule {m}_module modules/mod_{m}.so" for m in MODULOS)
    with open(conf, "w", encoding="utf-8") as f:
        f.write(CONF.format(root=SERVER_ROOT, tmp=tmp, aqui=AQUI, sitio=SITIO, demos=DEMOS, modulos=lineas))
    proc = subprocess.Popen([HTTPD, "-f", conf], stdout=subprocess.PIPE, stderr=subprocess.STDOUT)
    for _ in range(50):
        if proc.poll() is not None:
            break
        try:
            pedir(SITIO, "/img/foto.jpg")
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
    tmp = tempfile.mkdtemp(prefix="httpd-clase11-").replace("\\", "/")
    try:
        proc = arrancar(tmp)
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
        print(f"\n{'Todas las comprobaciones OK' if not fallos else f'{fallos} FALLOS'}")
        return 1 if fallos else 0
    finally:
        shutil.rmtree(tmp, ignore_errors=True)


if __name__ == "__main__":
    sys.exit(main())
