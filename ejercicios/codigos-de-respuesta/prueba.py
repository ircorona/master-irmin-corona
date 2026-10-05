"""Levanta un Apache de usar y tirar (el de Laragon, con config propia) y
comprueba cada regla con peticiones reales. Sale con 0 si todo pasa.

    python prueba.py

No toca el Apache de Laragon: puerto propio (8099 sitio, 8098 demos),
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
SITIO, DEMOS = 8099, 8098

CONF = f"""
ServerRoot "{SERVER_ROOT}"
Listen 127.0.0.1:{SITIO}
Listen 127.0.0.1:{DEMOS}
ServerName localhost
LoadModule alias_module modules/mod_alias.so
LoadModule authz_core_module modules/mod_authz_core.so
LoadModule dir_module modules/mod_dir.so
LoadModule headers_module modules/mod_headers.so
LoadModule mime_module modules/mod_mime.so
LoadModule rewrite_module modules/mod_rewrite.so
DirectoryIndex index.html
ErrorLog "{{tmp}}/error.log"
PidFile "{{tmp}}/httpd.pid"
# Sin esto Apache sube por TODOS los directorios padre buscando .htaccess
# (y se tropieza con el del repo). Es justo lo que cuenta la doc de Apache.
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


def pedir(puerto, ruta):
    c = http.client.HTTPConnection("127.0.0.1", puerto, timeout=5)
    c.request("GET", ruta)
    r = c.getresponse()
    r.read()
    loc = r.getheader("Location") or ""
    # Location absoluta -> solo la ruta, para comparar
    if loc.startswith("http"):
        loc = "/" + loc.split("/", 3)[3] if loc.count("/") >= 3 else "/"
    return r.status, loc, r.getheader("Retry-After")


def seguir(puerto, ruta, maximo=10):
    """Sigue redirecciones como un navegador. Devuelve la lista de saltos."""
    saltos = []
    for _ in range(maximo):
        status, loc, _ = pedir(puerto, ruta)
        saltos.append((status, ruta))
        if status not in (301, 302, 303, 307, 308):
            return saltos, False
        ruta = loc
    return saltos, True  # se ha agotado: bucle


# (puerto, ruta, código esperado, Location esperada o None)
CASOS = [
    # Ejercicio 1: directorio -> directorio, con hijos, en un solo salto
    (SITIO, "/blog", 301, "/noticias/"),
    (SITIO, "/blog/", 301, "/noticias/"),
    (SITIO, "/blog/articulo-1/", 301, "/noticias/articulo-1/"),
    (SITIO, "/blog/articulo-2/", 301, "/noticias/articulo-2/"),
    (SITIO, "/blogger/", 404, None),
    (SITIO, "/noticias/articulo-1/", 200, None),
    # Ejercicio 2: 503 solo en una página
    (SITIO, "/mantenimiento/", 503, None),
    (SITIO, "/mantenimiento", 503, None),
    (SITIO, "/noticias/", 200, None),
    # Ejercicio 3: todo un directorio -> una sola página, con 302
    (SITIO, "/promociones", 302, "/oferta-san-valentin/"),
    (SITIO, "/promociones/", 302, "/oferta-san-valentin/"),
    (SITIO, "/promociones/2025/rebajas/", 302, "/oferta-san-valentin/"),
    (SITIO, "/oferta-san-valentin/", 200, None),
    # Demos: QR de clase (sin anclar) vs anclado
    (DEMOS, "/qr-clase/?qr=servilletas", 302, "/oferta-san-valentin/"),
    (DEMOS, "/qr-clase/?xqr=servilletas2", 302, "/oferta-san-valentin/"),
    (DEMOS, "/qr-anclado/?qr=servilletas", 302, "/oferta-san-valentin/"),
    (DEMOS, "/qr-anclado/?utm_source=x&qr=servilletas", 302, "/oferta-san-valentin/"),
    (DEMOS, "/qr-anclado/?xqr=servilletas2", 404, None),
    # Demos: quitar la barra si no es directorio
    (DEMOS, "/sin-barra/pagina/", 301, "/sin-barra/pagina"),
    # Demos: <Files>
    (DEMOS, "/files-clase/.htpasswd", 500, None),      # pizarra: no arranca
    (DEMOS, "/files-literal/.htpasswd", 200, None),    # ".ht": se filtra
    (DEMOS, "/files-literal/.htaccess", 200, None),    # y el propio .htaccess
    (DEMOS, "/files-corregido/.htpasswd", 403, None),  # ".ht*": bloqueado
    (DEMOS, "/files-corregido/.htaccess", 403, None),
]


def main():
    tmp = tempfile.mkdtemp(prefix="httpd-clase06-").replace("\\", "/")
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
        for puerto, ruta, codigo, destino in CASOS:
            status, loc, retry = pedir(puerto, ruta)
            ok = status == codigo and (destino is None or loc == destino)
            extra = f" -> {loc}" if loc else ""
            extra += f"  Retry-After: {retry}" if retry else ""
            print(f"{'OK ' if ok else 'MAL'} :{puerto} {ruta:42} {status}{extra}")
            fallos += not ok

        # 503 con Retry-After
        _, _, retry = pedir(SITIO, "/mantenimiento/")
        ok = retry == "3600"
        print(f"{'OK ' if ok else 'MAL'} Retry-After en el 503 = {retry}")
        fallos += not ok

        # El bucle de la foto: debe agotar los 10 saltos
        saltos, bucle = seguir(DEMOS, "/sobre-mi")
        print(f"{'OK ' if bucle else 'MAL'} bucle sobre-mi <-> carpeta/archivo-carpeta: "
              f"{len(saltos)} saltos sin llegar a un 200 "
              f"({' > '.join(r for _, r in saltos[:3])} > ...)")
        fallos += not bucle

        # Cadena: ninguna redirección del ejercicio puede tener más de un salto
        for ruta in ["/blog", "/blog/articulo-1/", "/promociones/x/"]:
            saltos, _ = seguir(SITIO, ruta)
            ok = len(saltos) == 2 and saltos[-1][0] == 200
            print(f"{'OK ' if ok else 'MAL'} {ruta} llega a 200 en {len(saltos) - 1} salto(s)")
            fallos += not ok

        total = len(CASOS) + 1 + 1 + 3
        print(f"\n{total - fallos}/{total} comprobaciones OK")
        return 1 if fallos else 0
    finally:
        # En Windows Apache lanza un proceso hijo: hay que matar el árbol entero
        subprocess.run(["taskkill", "/T", "/F", "/PID", str(proc.pid)], capture_output=True)
        proc.wait(timeout=10)
        shutil.rmtree(tmp, ignore_errors=True)


if __name__ == "__main__":
    sys.exit(main())
