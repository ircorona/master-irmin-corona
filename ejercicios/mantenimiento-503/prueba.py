"""Prueba el .htaccess de mantenimiento de la clase 20 y su versión mejorada en un Apache temporal.

Uso: python prueba.py [--httpd RUTA_A_httpd.exe] [--puerto 8136]

Usa el binario de Apache de Laragon con su propio httpd.conf en una carpeta temporal
(no toca la configuración de Laragon). Cada variante se prueba como:
  visitante      -> IPv4 que no es la permitida
  equipo IPv4    -> la IP permitida
  equipo IPv6    -> el mismo equipo saliendo por IPv6 (::1)

Sale con 0 si todos los casos dan lo esperado.
"""
import argparse
import glob
import subprocess
import sys
import tempfile
import time
from pathlib import Path

sys.stdout.reconfigure(encoding="utf-8")

AQUI = Path(__file__).parent / "variantes"
IP4_EJEMPLO = r"203\.0\.113\.5"
IP6_EJEMPLO = r"2001:db8::5"
CONF = """ServerRoot "{raiz}"
Listen 127.0.0.1:{puerto}
Listen [::1]:{puerto}
ServerName localhost
LoadModule authz_core_module modules/mod_authz_core.so
LoadModule dir_module modules/mod_dir.so
LoadModule headers_module modules/mod_headers.so
LoadModule mime_module modules/mod_mime.so
LoadModule rewrite_module modules/mod_rewrite.so
TypesConfig conf/mime.types
AddDefaultCharset off
PidFile "{tmp}/httpd.pid"
ErrorLog "{tmp}/error.log"
DocumentRoot "{web}"
DirectoryIndex index.html
<Directory "{web}">
    AllowOverride All
    Require all granted
</Directory>
"""
# variante, quién, IPv4 permitida, host, esperado: (código /, código robots, Retry-After)
CASOS = [
    ("clase", "visitante", r"198\.51\.100\.7", "127.0.0.1", (503, 503, False)),
    ("clase", "equipo IPv4", r"127\.0\.0\.1", "127.0.0.1", (200, 200, False)),
    ("clase", "equipo IPv6", r"127\.0\.0\.1", "[::1]", (503, 503, False)),
    ("mejorada", "visitante", r"198\.51\.100\.7", "127.0.0.1", (503, 200, True)),
    ("mejorada", "equipo IPv4", r"127\.0\.0\.1", "127.0.0.1", (200, 200, False)),
    ("mejorada", "equipo IPv6", r"127\.0\.0\.1", "[::1]", (200, 200, False)),
]


def peticion(host, puerto, ruta):
    r = subprocess.run(
        ["curl", "-s", "--noproxy", "*", "--max-time", "5", "-D", "-", f"http://{host}:{puerto}{ruta}"],
        capture_output=True,
    )
    texto = r.stdout.decode("utf-8", errors="replace")
    cab, _, cuerpo = texto.partition("\r\n\r\n")
    lineas = cab.splitlines()
    codigo = int(lineas[0].split()[1]) if lineas else 0
    h = {l.split(":", 1)[0].lower(): l.split(":", 1)[1].strip() for l in lineas[1:] if ":" in l}
    return codigo, h, cuerpo


def arrancar(httpd, conf, puerto):
    proceso = subprocess.Popen([httpd, "-f", conf], stdout=subprocess.DEVNULL, stderr=subprocess.PIPE)
    for _ in range(50):
        if peticion("127.0.0.1", puerto, "/")[0]:
            return proceso
        time.sleep(0.1)
    proceso.terminate()
    raise SystemExit("Apache no arranca: " + proceso.stderr.read().decode(errors="replace"))


def main():
    p = argparse.ArgumentParser()
    p.add_argument("--httpd", default=(glob.glob("C:/laragon/bin/apache/*/bin/httpd.exe") or [""])[-1])
    p.add_argument("--puerto", type=int, default=8136)
    a = p.parse_args()
    if not a.httpd:
        raise SystemExit("No encuentro httpd.exe; indícalo con --httpd")
    raiz = Path(a.httpd).parent.parent.as_posix()

    bien, cuerpos = 0, {}
    with tempfile.TemporaryDirectory() as t:
        tmp = Path(t).as_posix()
        web = Path(t) / "web"
        web.mkdir()
        (web / "index.html").write_text("<!doctype html><title>web real</title>", encoding="utf-8")
        (web / "robots.txt").write_text("User-agent: *\nDisallow:\n", encoding="utf-8")
        (web / "mantenimiento.html").write_bytes((AQUI / "mantenimiento.html").read_bytes())
        conf = Path(t) / "httpd.conf"
        conf.write_text(CONF.format(raiz=raiz, puerto=a.puerto, tmp=tmp, web=web.as_posix()), encoding="utf-8")

        print(f"{'Variante':<10}{'Quién':<13}{'/':<6}{'robots':<8}{'Retry-After':<13}{'Content-Type':<32}Resultado")
        for variante, quien, ip4, host, esperado in CASOS:
            regla = (AQUI / f"{variante}.htaccess").read_text(encoding="utf-8")
            regla = regla.replace(IP4_EJEMPLO, ip4).replace(IP6_EJEMPLO, "::1")
            (web / ".htaccess").write_text(regla, encoding="utf-8")
            proceso = arrancar(a.httpd, str(conf), a.puerto)
            try:
                c1, h1, b1 = peticion(host, a.puerto, "/")
                c2, _, _ = peticion(host, a.puerto, "/robots.txt")
            finally:
                proceso.terminate()
                proceso.wait()
            obtenido = (c1, c2, "retry-after" in h1)
            ok = obtenido == esperado
            bien += ok
            if c1 == 503:
                cuerpos[variante] = b1
            print(f"{variante:<10}{quien:<13}{c1:<6}{c2:<8}{h1.get('retry-after', 'no'):<13}"
                  f"{h1.get('content-type', '-'):<32}{'correcto' if ok else 'FALLO'}")

    print("\nCuerpo del 503 (primeros 70 caracteres):")
    for v, b in cuerpos.items():
        print(f"  {v:<10}{b[:70]!r}")
    print(f"\nCasos correctos: {bien} de {len(CASOS)}")
    return 0 if bien == len(CASOS) else 1


if __name__ == "__main__":
    sys.exit(main())
