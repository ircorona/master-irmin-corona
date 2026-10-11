"""Levanta un nginx de usar y tirar (el de Laragon, con config propia) y
comprueba los bloqueos por IP con peticiones reales. Sale con 0 si todo pasa.

    python prueba.py            # las comprobaciones
    python prueba.py --servir   # deja nginx arrancado: curl -H "X-Real-IP: 216.24.213.200" http://127.0.0.1:8002/

Todas las peticiones salen de 127.0.0.1. Para simular otra IP se manda en la
cabecera X-Real-IP, y los server que confían en 127.0.0.1 (set_real_ip_from)
la usan como IP del visitante, igual que detrás de Cloudflare.

Puertos (no toca el nginx ni el Apache de Laragon):
  8001  clase.conf      el código de clase, tal cual
  8002  ejercicio.conf  deny primero; /privado/ solo para la oficina
  8003  demos.conf      herencia: una location con su deny pierde los del server
  8004  demos.conf      sin realip: nginx ve la IP del proxy
  8005  demos.conf      la lista con geo
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
CLASE, EJ, HERENCIA, SIN_REALIP, GEO = 8001, 8002, 8003, 8004, 8005

BLOQUEADAS = ["216.24.213.200", "216.24.213.230", "216.24.213.250", "216.24.213.206"]
VECINA = "216.24.213.201"       # misma red, no está en la lista
OFICINA = "203.0.113.10"        # IP de documentación (RFC 5737)
CUALQUIERA = "198.51.100.20"    # otra IP de documentación

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


def pedir(puerto, ruta, ip=None):
    c = http.client.HTTPConnection("127.0.0.1", puerto, timeout=5)
    c.request("GET", ruta, headers={"X-Real-IP": ip} if ip else {})
    r = c.getresponse()
    r.read()
    return r.status


def caso(puerto, ruta, ip, codigo, nota):
    global fallos
    s = pedir(puerto, ruta, ip)
    fallos += s != codigo
    print(f"{'OK ' if s == codigo else 'MAL'} :{puerto} {ruta:12} {ip or '-':16} {s}   {nota}")


def comprobar():
    print("== Código de clase: allow all primero")
    for ip in BLOQUEADAS:
        caso(CLASE, "/", ip, 200, "ENTRA: allow all casa primero y los deny no se leen")
    caso(CLASE, "/", CUALQUIERA, 200, "cualquier otra IP entra")

    print("\n== Ejercicio: deny primero")
    for ip in BLOQUEADAS:
        caso(EJ, "/", ip, 403, "bloqueada")
    caso(EJ, "/robots.txt", BLOQUEADAS[0], 403, "en toda la web, también el robots.txt")
    caso(EJ, "/", VECINA, 200, "216.24.213.201 no está en la lista: entra")
    caso(EJ, "/", CUALQUIERA, 200, "cualquier otra IP entra")
    caso(EJ, "/privado/", OFICINA, 200, "/privado/: la oficina entra")
    caso(EJ, "/privado/", CUALQUIERA, 403, "/privado/: el resto no")
    caso(EJ, "/privado/", BLOQUEADAS[0], 403, "/privado/: las bloqueadas tampoco (deny all)")

    print("\n== Demo: herencia")
    caso(HERENCIA, "/", BLOQUEADAS[0], 403, "en la portada, bloqueada")
    caso(HERENCIA, "/privado/", "198.51.100.7", 403, "en /privado/, la IP nueva bloqueada")
    caso(HERENCIA, "/privado/", BLOQUEADAS[0], 200, "pero las del server ENTRAN en /privado/")

    print("\n== Demo: sin realip detrás de un proxy")
    caso(SIN_REALIP, "/", BLOQUEADAS[0], 200, "ENTRA: nginx ve 127.0.0.1 (el proxy), no la IP del visitante")

    print("\n== Demo: geo")
    for ip in BLOQUEADAS:
        caso(GEO, "/", ip, 403, "bloqueada")
    caso(GEO, "/", VECINA, 200, "la vecina entra")


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
            pedir(EJ, "/")
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
    tmp = tempfile.mkdtemp(prefix="nginx-clase19-").replace("\\", "/")
    try:
        preparar(tmp)
        proc = arrancar(tmp)
        try:
            if "--servir" in sys.argv:
                print(f"nginx en :{CLASE}-:{GEO} (tmp {tmp}). Ctrl+C para parar.")
                try:
                    while True:
                        time.sleep(1)
                except KeyboardInterrupt:
                    return 0
            comprobar()
        finally:
            parar(proc, tmp)
        print(f"\n{'Todas las comprobaciones OK' if not fallos else f'{fallos} FALLOS'}")
        return 1 if fallos else 0
    finally:
        shutil.rmtree(tmp, ignore_errors=True)


if __name__ == "__main__":
    sys.exit(main())
