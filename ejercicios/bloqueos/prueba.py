"""Levanta un Apache de usar y tirar (el de Laragon, con config propia) y
comprueba los tres bloqueos con peticiones reales. Sale con 0 si todo pasa.

    python prueba.py

Arranca Apache dos veces:
  1. con todos los módulos, para el ejercicio y las demos;
  2. SIN mod_access_compat ni mod_authn_file, para ver qué hace el código de
     clase en un servidor al que le falta un módulo.

El .htpasswd se genera en cada ejecución con el htpasswd.exe de Laragon, en
una carpeta temporal fuera de la web. No se guarda ninguna contraseña en el repo.
No toca el Apache de Laragon: puertos 8095 (sitio) y 8094 (demos).
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
HTTPD = sorted(glob.glob("C:/laragon/bin/apache/httpd-*/bin/httpd.exe"))[-1]
HTPASSWD = os.path.join(os.path.dirname(HTTPD), "htpasswd.exe")
SERVER_ROOT = os.path.dirname(os.path.dirname(HTTPD)).replace("\\", "/")
SITIO, DEMOS = 8095, 8094
USUARIO, CLAVE = "staging", "clase-08-prueba"

MODULOS = [
    "authz_core", "authz_host", "authn_core", "auth_basic", "authz_user",
    "authn_file", "access_compat", "dir", "mime", "rewrite",
]

CONF = """
ServerRoot "{root}"
Define PASSFILE "{tmp}/pass/.htpasswd"
Listen 127.0.0.1:{sitio}
Listen 127.0.0.1:{demos}
ServerName localhost
{modulos}
TypesConfig conf/mime.types
DirectoryIndex index.html
ErrorLog "{tmp}/error.log"
PidFile "{tmp}/httpd.pid"
<Directory "/">
    AllowOverride None
</Directory>
<Directory "{aqui}">
    AllowOverride All
    Require all granted
</Directory>
<VirtualHost *:{sitio}>
    DocumentRoot "{aqui}/sitio"
</VirtualHost>
<VirtualHost *:{demos}>
    DocumentRoot "{aqui}/demos"
</VirtualHost>
"""

UA_CHROME = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36"
UA_FROG = "Mozilla/5.0 (compatible; Screaming Frog SEO Spider/21.0)"
UA_CHATGPT = "Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko); compatible; ChatGPT-User/1.0; +https://openai.com/bot"
UA_GOOGLEBOT = "Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)"
UA_SEMRUSH = "Mozilla/5.0 (compatible; SemrushBot-SI/0.97; +http://www.semrush.com/bot.html)"
BIEN = (USUARIO, CLAVE)
MAL_ = (USUARIO, "otra")

# (puerto, ruta, host, user-agent, credenciales, código, texto esperado en
#  cabeceras o cuerpo o None, qué demuestra)
CASOS = [
    # Ejercicio 1: IP
    (SITIO, "/", None, UA_CHROME, None, 200, None, "la web abierta"),
    (SITIO, "/sitemap-lang.xml", None, UA_CHROME, None, 403, "No tienes acceso",
     "sitemap solo para 203.0.113.10; nosotros somos 127.0.0.1"),
    # Ejercicio 2: contraseña solo en staging
    (SITIO, "/", "staging.master-irmin-corona.test", UA_CHROME, None, 401,
     'Basic realm="Staging"', "staging pide contraseña"),
    (SITIO, "/", "staging.master-irmin-corona.test", UA_CHROME, MAL_, 401, None, "contraseña mala"),
    (SITIO, "/", "staging.master-irmin-corona.test", UA_CHROME, BIEN, 200, None, "contraseña buena"),
    (SITIO, "/", "master-irmin-corona.test", UA_CHROME, None, 200, None, "producción sin contraseña"),
    (SITIO, "/.htpasswd", None, UA_CHROME, None, 404, None, ".htpasswd fuera de la web"),
    # Ejercicio 3: user-agent
    (SITIO, "/", None, UA_FROG, None, 403, None, "Screaming Frog"),
    (SITIO, "/", None, UA_CHATGPT, None, 403, None, "ChatGPT-User"),
    (SITIO, "/", None, UA_SEMRUSH, None, 403, None, "SemrushBot-SI"),
    (SITIO, "/", None, UA_GOOGLEBOT, None, 200, None, "Googlebot entra en la web"),
    (SITIO, "/seo-avanzado/", None, UA_GOOGLEBOT, None, 403, None, "...pero no en /seo-avanzado/"),
    (SITIO, "/seo-avanzado", None, UA_GOOGLEBOT, None, 403, None, "ni sin barra"),
    (SITIO, "/seo-avanzado/", None, UA_CHROME, None, 200, None, "un navegador sí"),
    (SITIO, "/seo-avanzado/", None, UA_GOOGLEBOT.replace("Googlebot", "Googl3bot"), None, 200, None,
     "un UA con una letra cambiada se cuela"),
    # Demos con todos los módulos
    (DEMOS, "/ip/sitemap-lang.xml", None, UA_CHROME, None, 403, None, "Order/Deny/Allow (2.2) funciona con compat"),
    (DEMOS, "/password/", "master-testing.test", UA_CHROME, None, 401, "MALA SUERTE",
     "401 de clase: sale el texto"),
    (DEMOS, "/password/", "master-testing.test", UA_CHROME, None, 401, "Iniciar sesi",
     "AuthName con tilde"),
    (DEMOS, "/ua/", None, UA_FROG, None, 403, None, "código en MAYÚSCULAS: Apache lo acepta"),
    (DEMOS, "/ua/", None, "inventado/1.0", None, 403, None, "INVENTADO con [NC]"),
    (DEMOS, "/ua/", None, UA_CHROME, None, 200, None, "navegador"),
]

# Segunda pasada: sin mod_access_compat ni mod_authn_file
CASOS_SIN_MODULOS = [
    (DEMOS, "/ip/sitemap-lang.xml", None, UA_CHROME, None, 500, None,
     "sin mod_access_compat: 'order' es desconocida -> 500"),
    (DEMOS, "/password/", "master-testing.test", UA_CHROME, None, 200, None,
     "sin mod_authn_file: <IfModule> se salta todo -> web ABIERTA"),
    (SITIO, "/", "staging.master-irmin-corona.test", UA_CHROME, None, 500, None,
     "el ejercicio sin <IfModule>: 500, nunca abierta"),
]


def pedir(puerto, ruta, host=None, ua=None, cred=None):
    c = http.client.HTTPConnection("127.0.0.1", puerto, timeout=5)
    h = {"User-Agent": ua or UA_CHROME}
    if host:
        h["Host"] = host
    if cred:
        h["Authorization"] = "Basic " + base64.b64encode(":".join(cred).encode()).decode()
    c.request("GET", ruta, headers=h)
    r = c.getresponse()
    cuerpo = r.read().decode("utf-8", errors="replace")
    cabeceras = "\n".join(f"{k}: {v}" for k, v in r.getheaders())
    return r.status, cabeceras, cuerpo


def arrancar(tmp, modulos):
    conf = os.path.join(tmp, "httpd.conf")
    lineas = "\n".join(f"LoadModule {m}_module modules/mod_{m}.so" for m in modulos)
    with open(conf, "w", encoding="utf-8") as f:
        f.write(CONF.format(root=SERVER_ROOT, tmp=tmp, aqui=AQUI, sitio=SITIO,
                            demos=DEMOS, modulos=lineas))
    proc = subprocess.Popen([HTTPD, "-f", conf], stdout=subprocess.PIPE, stderr=subprocess.STDOUT)
    for _ in range(50):
        try:
            pedir(SITIO, "/")
            return proc
        except OSError:
            time.sleep(0.1)
    parar(proc)
    raise SystemExit("Apache no arrancó: " + proc.stdout.read().decode(errors="replace"))


def parar(proc):
    # En Windows Apache lanza un proceso hijo: hay que matar el árbol entero
    subprocess.run(["taskkill", "/T", "/F", "/PID", str(proc.pid)], capture_output=True)
    proc.wait(timeout=10)
    time.sleep(0.5)  # que suelte los puertos antes de la segunda pasada


def comprobar(casos):
    fallos = 0
    for puerto, ruta, host, ua, cred, codigo, texto, nota in casos:
        status, cabeceras, cuerpo = pedir(puerto, ruta, host, ua, cred)
        ok = status == codigo and (texto is None or texto in cabeceras or texto in cuerpo)
        fallos += not ok
        print(f"{'OK ' if ok else 'MAL'} :{puerto} {ruta:22} {status}  {nota}")
        if not ok and texto:
            print(f"      no aparece {texto!r}")
    return fallos


def main():
    tmp = tempfile.mkdtemp(prefix="httpd-clase08-").replace("\\", "/")
    os.makedirs(f"{tmp}/pass")
    # -c crea el fichero, -b toma la clave de la línea de órdenes, -B usa bcrypt
    subprocess.run([HTPASSWD, "-cbB", f"{tmp}/pass/.htpasswd", USUARIO, CLAVE],
                   check=True, capture_output=True)
    try:
        print("== Con todos los módulos")
        proc = arrancar(tmp, MODULOS)
        try:
            fallos = comprobar(CASOS)
        finally:
            parar(proc)

        print("\n== Sin mod_access_compat ni mod_authn_file")
        proc = arrancar(tmp, [m for m in MODULOS if m not in ("access_compat", "authn_file")])
        try:
            fallos += comprobar(CASOS_SIN_MODULOS)
        finally:
            parar(proc)

        total = len(CASOS) + len(CASOS_SIN_MODULOS)
        print(f"\n{total - fallos}/{total} comprobaciones OK")
        return 1 if fallos else 0
    finally:
        shutil.rmtree(tmp, ignore_errors=True)


if __name__ == "__main__":
    sys.exit(main())
