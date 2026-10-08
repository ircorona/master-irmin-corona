"""Levanta un Apache de usar y tirar (el de Laragon, con config propia) y
comprueba las cabeceras de cada regla con peticiones reales. Sale con 0 si
todo pasa.

    python prueba.py

No toca el Apache de Laragon: puerto propio (8097 sitio, 8096 demos),
config y logs en una carpeta temporal.
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
SITIO, DEMOS = 8097, 8096

CONF = f"""
ServerRoot "{SERVER_ROOT}"
Listen 127.0.0.1:{SITIO}
Listen 127.0.0.1:{DEMOS}
ServerName localhost
LoadModule authz_core_module modules/mod_authz_core.so
LoadModule dir_module modules/mod_dir.so
LoadModule headers_module modules/mod_headers.so
LoadModule mime_module modules/mod_mime.so
LoadModule rewrite_module modules/mod_rewrite.so
TypesConfig conf/mime.types
DirectoryIndex index.html
ErrorLog "{{tmp}}/error.log"
PidFile "{{tmp}}/httpd.pid"
# Sin esto Apache sube por todos los directorios padre buscando .htaccess
# y se tropieza con el de la raíz del repo (lo vimos en la clase 06).
<Directory "/">
    AllowOverride None
</Directory>
<Directory "{AQUI}">
    AllowOverride All
    Require all granted
</Directory>
<VirtualHost *:{SITIO}>
    DocumentRoot "{AQUI}/sitio"
</VirtualHost>
<VirtualHost *:{DEMOS}>
    DocumentRoot "{AQUI}/demos"
</VirtualHost>
"""

CANONICAL = '<https://master-irmin-corona.test/sobre-mi/>; rel="canonical"'
CANONICAL_CLASE = "<https://carlos.sanchezdonate.com>; rel='canonical'"
ASDRUBAL = "Pagina con el SEO revisado por Asdrubal SEO SL"
NO = None  # la cabecera NO debe aparecer

# (puerto, ruta, código, {cabecera: valor esperado o NO}, qué demuestra)
CASOS = [
    # Ejercicio 1: canonical en una página concreta
    (SITIO, "/sobre-mi/", 200, {"Link": CANONICAL, "X-Robots-Tag": NO}, "canonical solo aquí"),
    (SITIO, "/sobre-mi/?utm_source=x", 200, {"Link": CANONICAL}, "con parámetros también"),
    (SITIO, "/sobre-mi-equipo/", 200, {"Link": NO}, "anclado: no casa"),
    (SITIO, "/sobre-mi", 301, {"Link": NO}, "mod_dir añade la barra; el canonical llega en el 200"),
    (SITIO, "/", 200, {"Link": NO, "X-Robots-Tag": NO}, "portada limpia"),
    # Ejercicio 2: noindex en un directorio y lo que cuelga
    (SITIO, "/privado/", 200, {"X-Robots-Tag": "noindex"}, "el directorio"),
    (SITIO, "/privado/informe/", 200, {"X-Robots-Tag": "noindex"}, "una subpágina"),
    (SITIO, "/privado/tarifas.pdf", 200, {"X-Robots-Tag": "noindex"}, "un PDF: sin HTML, solo cabecera"),
    (SITIO, "/sobre-mi/", 200, {"X-Robots-Tag": NO}, "fuera del directorio"),
    # Demos: el código de la foto de clase, tal cual
    (DEMOS, "/", 200, {"X-Asdrubal": ASDRUBAL, "X-Robots-Tag": NO}, "cabecera global"),
    (DEMOS, "/sobre-mi/", 200, {"Link": CANONICAL_CLASE, "X-Robots-Tag": "noindex"}, "la URL buscada"),
    (DEMOS, "/sobre-mi-equipo/", 200, {"Link": CANONICAL_CLASE, "X-Robots-Tag": "noindex"},
     "SIN anclar: también le cae el noindex"),
    (DEMOS, "/contacto/", 200, {"X-Robots-Tag": NO, "X-Uri-Real": "/contacto/index.html"},
     "REQUEST_URI anclado NO casa: ya es index.html"),
    (DEMOS, "/tag/seo/", 200, {"X-Robots-Tag": NO, "X-Tag-The-Request": "noindex"},
     "reescritura tipo WordPress: REQUEST_URI pierde, THE_REQUEST gana"),
    (DEMOS, "/logo.svg", 200, {"X-Robots-Tag": "unavailable_after: 27 Jun 2045 15:00:00 PST"},
     "<Files ~> por extensión"),
]


def pedir(puerto, ruta):
    c = http.client.HTTPConnection("127.0.0.1", puerto, timeout=5)
    c.request("GET", ruta)
    r = c.getresponse()
    r.read()
    return r.status, {k.lower(): v for k, v in r.getheaders()}


def main():
    tmp = tempfile.mkdtemp(prefix="httpd-clase07-").replace("\\", "/")
    conf = os.path.join(tmp, "httpd.conf")
    with open(conf, "w", encoding="utf-8") as f:
        f.write(CONF.replace("{tmp}", tmp))
    proc = subprocess.Popen([HTTPD, "-f", conf], stdout=subprocess.PIPE, stderr=subprocess.STDOUT)
    try:
        for _ in range(50):
            try:
                pedir(SITIO, "/")
                break
            except OSError:
                time.sleep(0.1)
        else:
            print("Apache no arrancó:", proc.stdout.read().decode(errors="replace"))
            return 1

        fallos = 0
        for puerto, ruta, codigo, cabeceras, nota in CASOS:
            status, h = pedir(puerto, ruta)
            errores = [] if status == codigo else [f"código {status}"]
            vistas = []
            for nombre, esperado in cabeceras.items():
                real = h.get(nombre.lower())
                if real != esperado:
                    errores.append(f"{nombre}={real!r}")
                if real:
                    vistas.append(f"{nombre}: {real}")
            ok = not errores
            fallos += not ok
            print(f"{'OK ' if ok else 'MAL'} :{puerto} {ruta:26} {status}  {nota}")
            for v in vistas:
                print(f"      {v}")
            for e in errores:
                print(f"      esperado otra cosa: {e}")

        print(f"\n{len(CASOS) - fallos}/{len(CASOS)} comprobaciones OK")
        return 1 if fallos else 0
    finally:
        # En Windows Apache lanza un proceso hijo: hay que matar el árbol entero
        subprocess.run(["taskkill", "/T", "/F", "/PID", str(proc.pid)], capture_output=True)
        proc.wait(timeout=10)
        shutil.rmtree(tmp, ignore_errors=True)


if __name__ == "__main__":
    sys.exit(main())
