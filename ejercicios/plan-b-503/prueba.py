"""Prueba las tres variantes de .htaccess en un Apache temporal.

Uso: python prueba.py [--httpd RUTA_A_httpd.exe] [--puerto 8135]

Monta una carpeta temporal con su propio httpd.conf, usa el binario de Apache de Laragon
sin tocar su configuración, y arranca un servidor por caso. Cada variante se prueba dos
veces: como visitante (la IP permitida no es la nuestra) y como la IP permitida.

Sale con 0 si todos los casos dan el código esperado.
"""
import argparse
import glob
import subprocess
import sys
import tempfile
import time
from pathlib import Path

sys.stdout.reconfigure(encoding="utf-8")

AQUI = Path(__file__).parent
IP_EJEMPLO = r"203\.0\.113\.5"
LOCAL = r"127\.0\.0\.1"
OTRA = r"198\.51\.100\.7"
CASOS = [
    # variante, IP permitida, código en /, código en /robots.txt
    ("503-todo", OTRA, 503, 503),
    ("503-todo", LOCAL, 200, 200),
    ("503-sin-robots", OTRA, 503, 200),
    ("503-sin-robots", LOCAL, 200, 200),
    ("diapositiva-404-robots", OTRA, 503, 404),
    ("diapositiva-404-robots", LOCAL, 200, 200),
]
CONF = """ServerRoot "{raiz}"
Listen 127.0.0.1:{puerto}
ServerName localhost
LoadModule authz_core_module modules/mod_authz_core.so
LoadModule dir_module modules/mod_dir.so
LoadModule headers_module modules/mod_headers.so
LoadModule rewrite_module modules/mod_rewrite.so
PidFile "{tmp}/httpd.pid"
ErrorLog "{tmp}/error.log"
DocumentRoot "{web}"
DirectoryIndex index.html
<Directory "{web}">
    AllowOverride All
    Require all granted
</Directory>
"""


def peticion(puerto, ruta):
    salida = subprocess.run(
        ["curl", "-s", "-o", "NUL", "--noproxy", "*", "--max-time", "5", "-D", "-",
         f"http://127.0.0.1:{puerto}{ruta}"],
        capture_output=True, text=True,
    ).stdout
    cabeceras = salida.splitlines()
    codigo = int(cabeceras[0].split()[1]) if cabeceras else 0
    retry = any(c.lower().startswith("retry-after:") for c in cabeceras)
    return codigo, retry


def arrancar(httpd, conf, puerto):
    proceso = subprocess.Popen([httpd, "-f", conf], stdout=subprocess.DEVNULL, stderr=subprocess.PIPE)
    for _ in range(50):
        if peticion(puerto, "/")[0]:
            return proceso
        time.sleep(0.1)
    proceso.terminate()
    raise SystemExit("Apache no arranca: " + proceso.stderr.read().decode(errors="replace"))


def main():
    p = argparse.ArgumentParser()
    p.add_argument("--httpd", default=(glob.glob("C:/laragon/bin/apache/*/bin/httpd.exe") or [""])[-1])
    p.add_argument("--puerto", type=int, default=8135)
    a = p.parse_args()
    if not a.httpd:
        raise SystemExit("No encuentro httpd.exe; indícalo con --httpd")
    raiz = Path(a.httpd).parent.parent.as_posix()

    bien = 0
    with tempfile.TemporaryDirectory() as t:
        tmp = Path(t).as_posix()
        web = Path(t) / "web"
        web.mkdir()
        (web / "index.html").write_text("<!doctype html><title>web</title>", encoding="utf-8")
        (web / "robots.txt").write_text("User-agent: *\nDisallow:\n", encoding="utf-8")
        conf = Path(t) / "httpd.conf"
        conf.write_text(CONF.format(raiz=raiz, puerto=a.puerto, tmp=tmp, web=web.as_posix()), encoding="utf-8")

        print(f"{'Variante':<26}{'IP':<12}{'/':<8}{'robots.txt':<12}{'Retry-After':<13}Resultado")
        for variante, ip, inicio, robots in CASOS:
            regla = (AQUI / "variantes" / f"{variante}.htaccess").read_text(encoding="utf-8")
            (web / ".htaccess").write_text(regla.replace(IP_EJEMPLO, ip), encoding="utf-8")
            proceso = arrancar(a.httpd, str(conf), a.puerto)
            try:
                c1, r1 = peticion(a.puerto, "/")
                c2, _ = peticion(a.puerto, "/robots.txt")
            finally:
                proceso.terminate()
                proceso.wait()
            correcto = (c1, c2) == (inicio, robots) and r1 == (inicio == 503)
            bien += correcto
            nombre = "permitida" if ip == LOCAL else "visitante"
            print(f"{variante:<26}{nombre:<12}{c1:<8}{c2:<12}{'sí' if r1 else 'no':<13}"
                  f"{'correcto' if correcto else 'FALLO'}")

    print(f"\nCasos correctos: {bien} de {len(CASOS)}")
    return 0 if bien == len(CASOS) else 1


if __name__ == "__main__":
    sys.exit(main())
