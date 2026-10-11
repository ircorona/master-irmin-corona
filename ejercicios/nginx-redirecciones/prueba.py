"""Levanta un nginx de usar y tirar (el de Laragon, con config propia) y
comprueba las redirecciones de clase y las del ejercicio con peticiones reales.
Sale con 0 si todo pasa.

    python prueba.py            # las comprobaciones
    python prueba.py --servir   # deja nginx arrancado para mirar con curl

Puertos (no toca el nginx ni el Apache de Laragon):
  8051  clase-rewrite.conf         las dos líneas de rewrite
  8052  clase-location.conf        página -> página con return, y el 503
  8053  clase-location-host.conf   la variante con $scheme://$host
  8054  clase-index.conf           «Si todo va al index»
  8055  clase-sin-php.conf         «Convertir de .php a no .php»
  8056  ejercicio.conf             el ejercicio
  9151  php-cgi.exe de Laragon en modo FastCGI
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
FCGI = 9151
REWRITE, LOCATION, HOST, INDEX, SIN_PHP, EJ = 8051, 8052, 8053, 8054, 8055, 8056

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


def pedir(puerto, ruta, host="sanchezdonate.test", metodo="GET", cuerpo=None):
    """Petición sin seguir redirecciones. Devuelve (código, Location, cabeceras, cuerpo)."""
    c = http.client.HTTPConnection("127.0.0.1", puerto, timeout=5)
    h = {"Host": host}
    if cuerpo:
        h["Content-Type"] = "application/x-www-form-urlencoded"
    c.request(metodo, ruta, body=cuerpo, headers=h)
    r = c.getresponse()
    return r.status, r.getheader("Location"), {k.lower(): v for k, v in r.getheaders()}, r.read().decode("utf-8", "replace")


def caso(puerto, ruta, codigo, destino=None, texto=None, nota="", **kw):
    global fallos
    s, loc, _, b = pedir(puerto, ruta, **kw)
    if loc:
        loc = loc.replace(f":{puerto}/", "/")  # nginx añade el puerto al no ser el 80
    bien = s == codigo and (destino is None or loc == destino) and (texto is None or texto in b)
    fallos += not bien
    print(f"{'OK ' if bien else 'MAL'} :{puerto} {kw.get('metodo', 'GET'):4} {ruta:32} {s}"
          + (f" -> {loc}" if loc else "") + f"   {nota}")


def ok(cond, que):
    global fallos
    fallos += not cond
    print(f"{'OK ' if cond else 'MAL'} {que}")


def comprobar():
    print("== Clase: las dos líneas de rewrite")
    caso(REWRITE, "/curiosidades/un-dato/", 301, "https://sanchezdonate.test/",
         nota="todo el directorio a la portada (Google lo trata como soft 404)")
    caso(REWRITE, "/curiosidades/un-dato/?utm=x", 301, "https://sanchezdonate.test/?utm=x",
         nota="rewrite añade los parámetros solo")
    caso(REWRITE, "/curiosidades/", 301, "https://sanchezdonate.test/",
         nota="la segunda línea no llega a ejecutarse: la primera casa antes y corta")

    print("\n== Clase: location + return")
    caso(LOCATION, "/curiosidades/", 301, "https://example.com/datos-curiosos/", nota="página -> página")
    caso(LOCATION, "/curiosidades/?utm=x", 301, "https://example.com/datos-curiosos/",
         nota="return pierde los parámetros")
    caso(LOCATION, "/curiosidades", 404, nota="sin barra: = es exacto, no casa")
    caso(LOCATION, "/contacto/", 503, nota="código forzado 503")
    _, _, h, _ = pedir(LOCATION, "/contacto/")
    ok("retry-after" not in h, "...sin Retry-After: Google no sabe cuándo volver")
    caso(HOST, "/curiosidades/", 301, "http://sanchezdonate.test//datos-curiosos/",
         nota="la variante «escalable» redirige a //datos-curiosos/ (doble barra)")

    print("\n== Clase: «Si todo va al index»")
    caso(INDEX, "/contacto", 200, texto="CONTACTO", nota="sirve contacto.php sin extensión")
    caso(INDEX, "/contacto.php", 200, texto="CONTACTO",
         nota="la .php sigue dando 200: el if de location / nunca la ve")
    caso(INDEX, "/no-existe", 404, nota="no existe: 404")
    caso(INDEX, "/", 404, nota="la PORTADA da 404: sin $uri/ no se aplica index, y /.php no existe")

    print("\n== Clase: «Convertir de .php a no .php»")
    caso(SIN_PHP, "/contacto.php", 301, "http://sanchezdonate.test/contacto", nota="quita la extensión")
    caso(SIN_PHP, "/contacto", 200, texto="CONTACTO", nota="y la URL limpia funciona")
    caso(SIN_PHP, "/contacto.php?id=7", 301, "http://sanchezdonate.test/contacto",
         nota="pierde los parámetros")
    caso(SIN_PHP, "/index.php", 301, "http://sanchezdonate.test/index", nota="index.php -> /index, no a /")
    caso(SIN_PHP, "/blog/index.php", 301, "http://sanchezdonate.test/blog/index", nota="lo mismo en carpetas")
    caso(SIN_PHP, "/enviar.php", 301, "http://sanchezdonate.test/enviar",
         nota="un POST de formulario recibe 301 y se pierden los datos", metodo="POST", cuerpo="nombre=Irmin")

    print("\n== Ejercicio: redirecciones")
    caso(EJ, "/curiosidades/", 301, "http://mi-web.test/datos-curiosos/", nota="página -> página (relativa)",
         host="mi-web.test")
    caso(EJ, "/curiosidades/un-dato/?utm=x", 301, "http://mi-web.test/datos-curiosos/un-dato/?utm=x",
         nota="directorio -> directorio, estructura y parámetros", host="mi-web.test")
    caso(EJ, "/rebajas/", 302, "http://mi-web.test/datos-curiosos/", nota="temporal", host="mi-web.test")
    caso(EJ, "/viejo-blog/post-1/?ref=a", 301, "https://otro-dominio.test/blog/post-1/?ref=a",
         nota="a otro dominio", host="mi-web.test")
    caso(EJ, "/datos-curiosos/", 200, texto="DATOS CURIOSOS", nota="el destino responde 200 (sin cadenas)",
         host="mi-web.test")

    print("\n== Ejercicio: quitar .php")
    caso(EJ, "/contacto.php?id=7", 301, "http://mi-web.test/contacto?id=7", nota="conserva los parámetros",
         host="mi-web.test")
    caso(EJ, "/contacto", 200, texto="CONTACTO GET /contacto", nota="la URL limpia", host="mi-web.test")
    caso(EJ, "/index.php", 301, "http://mi-web.test/", nota="index.php -> /", host="mi-web.test")
    caso(EJ, "/blog/index.php", 301, "http://mi-web.test/blog/", nota="y en carpetas", host="mi-web.test")
    caso(EJ, "/blog/post.php", 301, "http://mi-web.test/blog/post", nota="", host="mi-web.test")
    caso(EJ, "/blog/post", 200, texto="POST DEL BLOG", nota="", host="mi-web.test")
    caso(EJ, "/enviar.php", 200, texto="ENVIADO POST Irmin", nota="un POST no se redirige: llegan los datos",
         host="mi-web.test", metodo="POST", cuerpo="nombre=Irmin")
    caso(EJ, "/no-existe", 404, nota="no existe: 404", host="mi-web.test")

    print("\n== Ejercicio: códigos forzados")
    caso(EJ, "/contacto-antiguo/", 410, nota="retirado", host="mi-web.test")
    caso(EJ, "/mantenimiento/", 503, nota="mantenimiento", host="mi-web.test")
    _, _, h, _ = pedir(EJ, "/mantenimiento/", host="mi-web.test")
    ok(h.get("retry-after") == "3600", "...con Retry-After: 3600")


def comprobar_sintaxis(tmp):
    """Errores que impiden arrancar: se ven con nginx -t."""
    print("\n== nginx -t sobre el código de clase")
    clase = open(f"{AQUI}/conf/clase-location.conf", encoding="utf-8").read()
    host = open(f"{AQUI}/conf/clase-location-host.conf", encoding="utf-8").read()
    bloque = host[host.index("location = /curiosidades/{"):host.rindex("}")]
    with open(f"{tmp}/conf/sites/clase-location.conf", "w", encoding="utf-8") as f:
        f.write(clase.rstrip()[:-1] + bloque + "}\n")
    r = subprocess.run([f"{NGINX}/nginx.exe", "-p", f"{tmp}/", "-t"], capture_output=True, text=True)
    ok(r.returncode != 0 and "duplicate location" in r.stderr,
       "las dos variantes de clase en el mismo server: «duplicate location», nginx no arranca")


def preparar(tmp):
    for d in ("conf/sites", "conf/comun", "logs", "temp"):
        os.makedirs(f"{tmp}/{d}", exist_ok=True)
    for f in ("mime.types", "fastcgi_params"):
        shutil.copy(f"{NGINX}/conf/{f}", f"{tmp}/conf/{f}")
    shutil.copy(f"{AQUI}/conf/comun/php.inc", f"{tmp}/conf/comun/php.inc")
    with open(f"{tmp}/conf/nginx.conf", "w", encoding="utf-8") as f:
        f.write(NGINX_CONF.format(fcgi=FCGI))
    for nombre in os.listdir(f"{AQUI}/conf"):
        if nombre.endswith(".conf"):
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
            pedir(EJ, "/datos-curiosos/", host="mi-web.test")
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
    tmp = tempfile.mkdtemp(prefix="nginx-clase14-").replace("\\", "/")
    try:
        preparar(tmp)
        proc = arrancar(tmp)
        try:
            if "--servir" in sys.argv:
                print(f"nginx en :8051-:8056 (tmp {tmp}). Ctrl+C para parar.")
                try:
                    while True:
                        time.sleep(1)
                except KeyboardInterrupt:
                    return 0
            comprobar()
        finally:
            parar(proc, tmp)
        comprobar_sintaxis(tmp)
        print(f"\n{'Todas las comprobaciones OK' if not fallos else f'{fallos} FALLOS'}")
        return 1 if fallos else 0
    finally:
        shutil.rmtree(tmp, ignore_errors=True)


if __name__ == "__main__":
    sys.exit(main())
