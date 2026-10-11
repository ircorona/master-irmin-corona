"""Levanta un nginx de usar y tirar (el de Laragon, con config propia) y
comprueba los bloqueos por user-agent con peticiones reales. Sale con 0 si
todo pasa.

    python prueba.py            # las comprobaciones
    python prueba.py --servir   # deja nginx arrancado para probar con curl -A

Puertos (no toca el nginx ni el Apache de Laragon):
  8011  clase.conf           el código de clase, tal cual
  8012  ejercicio.conf       Yandex fuera de la web; Screaming Frog fuera de /sobre-mi/
  8013  coco-location.conf   el opcional con un if dentro de la location
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
CLASE, EJ, COCO = 8011, 8012, 8013

# User-agents tal como los envían (los de Yandex, de los que publica Yandex)
CHROME = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36"
GOOGLEBOT = "Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)"
FROG = "Screaming Frog SEO Spider/21.0"
CHATGPT = "Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko); compatible; ChatGPT-User/1.0; +https://openai.com/bot"
SEMRUSH = "Mozilla/5.0 (compatible; SemrushBot-SI/0.97; +http://www.semrush.com/bot.html)"
SEMRUSH_BOT = "Mozilla/5.0 (compatible; SemrushBot/7~bl; +http://www.semrush.com/bot.html)"
YANDEX = "Mozilla/5.0 (compatible; YandexBot/3.0; +http://yandex.com/bots)"
YANDEX_IMG = "Mozilla/5.0 (compatible; YandexImages/3.0; +http://yandex.com/bots)"
YANDEX_MOVIL = ("Mozilla/5.0 (iPhone; CPU iPhone OS 8_1 like Mac OS X) AppleWebKit/600.1.4 (KHTML, like Gecko) "
                "Version/8.0 Mobile/12B411 Safari/600.1.4 (compatible; YandexMobileBot/3.0; +http://yandex.com/bots)")
YANDEX_BROWSER = ("Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) "
                  "Chrome/128.0.0.0 YaBrowser/24.10.0.0 Safari/537.36")

NGINX_CONF = """
worker_processes 1;
error_log logs/error.log warn;
pid logs/nginx.pid;
events { worker_connections 64; }
http {
    include mime.types;
    default_type application/octet-stream;
    access_log off;
    include sites/*.conf;
}
"""

fallos = 0


def pedir(puerto, ruta, ua):
    c = http.client.HTTPConnection("127.0.0.1", puerto, timeout=5)
    c.request("GET", ruta, headers={"User-Agent": ua})
    r = c.getresponse()
    r.read()
    return r.status


def caso(puerto, ruta, ua, codigo, nota):
    global fallos
    s = pedir(puerto, ruta, ua)
    fallos += s != codigo
    print(f"{'OK ' if s == codigo else 'MAL'} :{puerto} {ruta:28} {s}   {nota}")


def ok(cond, que):
    global fallos
    fallos += not cond
    print(f"{'OK ' if cond else 'MAL'} {que}")


def comprobar():
    print("== Código de clase")
    caso(CLASE, "/", FROG, 403, "Screaming Frog")
    caso(CLASE, "/", CHATGPT, 403, "ChatGPT-User")
    caso(CLASE, "/", SEMRUSH, 403, "SemrushBot-SI")
    caso(CLASE, "/", "inventado/1.0", 403, "«inventado»")
    caso(CLASE, "/", "INVENTADO/1.0", 403, "~* no distingue mayúsculas")
    caso(CLASE, "/", SEMRUSH_BOT, 200, "SemrushBot (el rastreador principal) pasa: solo están -BA y -SI")
    caso(CLASE, "/", "Mozilla/5.0 (compatible; Screaming at the Frog SEO Spider)", 403,
         "Screaming.*Frog casa de más: «Screaming at the Frog»")
    caso(CLASE, "/", "Screaming Frog SEO Spider/21.0".replace("Screaming Frog", "ScreamingFrog"), 403,
         "y sin espacio también")
    caso(CLASE, "/", GOOGLEBOT, 200, "Googlebot pasa")
    caso(CLASE, "/", CHROME, 200, "un navegador pasa")
    caso(CLASE, "/robots.txt", FROG, 403, "el robots.txt también queda bloqueado")

    print("\n== Ejercicio: Yandex fuera")
    for ua, nombre in ((YANDEX, "YandexBot"), (YANDEX_IMG, "YandexImages"), (YANDEX_MOVIL, "YandexMobileBot")):
        caso(EJ, "/", ua, 403, nombre)
    caso(EJ, "/blog/", YANDEX, 403, "en cualquier URL")
    caso(EJ, "/robots.txt", YANDEX, 200, "salvo el robots.txt: lee su Disallow y deja de insistir")
    caso(EJ, "/", YANDEX_BROWSER, 200, "el navegador de Yandex (YaBrowser) es una persona: pasa")
    caso(EJ, "/", GOOGLEBOT, 200, "Googlebot pasa")
    caso(EJ, "/", CHROME, 200, "un navegador pasa")

    print("\n== Ejercicio opcional: Screaming Frog fuera de /sobre-mi/")
    caso(EJ, "/sobre-mi/", FROG, 403, "/sobre-mi/")
    caso(EJ, "/sobre-mi", FROG, 403, "sin barra")
    caso(EJ, "/sobre-mi/index.html", FROG, 403, "pidiendo el fichero directamente")
    caso(EJ, "/sobre-mi-equipo/", FROG, 200, "/sobre-mi-equipo/ no (empieza igual)")
    caso(EJ, "/blog/", FROG, 200, "el resto de la web: pasa")
    caso(EJ, "/sobre-mi/", CHROME, 200, "un navegador en /sobre-mi/: pasa")

    print("\n== El opcional con if dentro de la location")
    caso(COCO, "/sobre-mi/", FROG, 403, "/sobre-mi/")
    caso(COCO, "/sobre-mi/index.html", FROG, 200, "/sobre-mi/index.html se COLA: no casa con ^/sobre-mi/?$")


def comprobar_sintaxis(tmp):
    print("\n== nginx -t: la regex con un espacio y sin comillas")
    with open(f"{tmp}/conf/sites/clase.conf", encoding="utf-8") as f:
        texto = f.read()
    with open(f"{tmp}/conf/sites/clase.conf", "w", encoding="utf-8") as f:
        f.write(texto.replace("Screaming.*Frog.*SEO.*Spider", "Screaming Frog SEO Spider"))
    r = subprocess.run([f"{NGINX}/nginx.exe", "-p", f"{tmp}/", "-t"], capture_output=True, text=True)
    ok(r.returncode != 0, "«Screaming Frog SEO Spider» sin comillas: nginx no arranca")
    print("      ", (r.stderr.strip().splitlines() or [""])[-2][:110])


def preparar(tmp):
    for d in ("conf/sites", "logs", "temp"):
        os.makedirs(f"{tmp}/{d}", exist_ok=True)
    shutil.copy(f"{NGINX}/conf/mime.types", f"{tmp}/conf/mime.types")
    with open(f"{tmp}/conf/nginx.conf", "w", encoding="utf-8") as f:
        f.write(NGINX_CONF)
    for nombre in os.listdir(f"{AQUI}/conf"):
        texto = open(f"{AQUI}/conf/{nombre}", encoding="utf-8").read().replace("__SITIO__", f"{AQUI}/sitio")
        with open(f"{tmp}/conf/sites/{nombre}", "w", encoding="utf-8") as f:
            f.write(texto)


def arrancar(tmp):
    r = subprocess.run([f"{NGINX}/nginx.exe", "-p", f"{tmp}/", "-t"], capture_output=True, text=True)
    if r.returncode != 0:
        raise SystemExit("nginx -t falló:\n" + r.stderr)
    proc = subprocess.Popen([f"{NGINX}/nginx.exe", "-p", f"{tmp}/"], cwd=tmp)
    for _ in range(50):
        try:
            pedir(EJ, "/", CHROME)
            return proc
        except OSError:
            time.sleep(0.1)
    parar(proc, tmp)
    raise SystemExit("nginx no arrancó: " + open(f"{tmp}/logs/error.log").read())


def parar(proc, tmp):
    subprocess.run([f"{NGINX}/nginx.exe", "-p", f"{tmp}/", "-s", "stop"], capture_output=True)
    subprocess.run(["taskkill", "/T", "/F", "/PID", str(proc.pid)], capture_output=True)
    proc.wait(timeout=10)
    time.sleep(0.5)


def main():
    sys.stdout.reconfigure(encoding="utf-8")
    tmp = tempfile.mkdtemp(prefix="nginx-clase17-").replace("\\", "/")
    try:
        preparar(tmp)
        proc = arrancar(tmp)
        try:
            if "--servir" in sys.argv:
                print(f"nginx en :{CLASE} :{EJ} :{COCO} (tmp {tmp}). Ctrl+C para parar.")
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
