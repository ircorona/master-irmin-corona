"""Dice si una URL está protegida contra la indexación o es indexable, y si eso es lo esperado.

Uso:
  python indexable.py --prueba                          5 casos en un servidor local
  python indexable.py --esperado staging URL [URL...]   un entorno de pruebas: debe estar bloqueado
  python indexable.py --esperado produccion URL [...]   la web ya lanzada: debe ser indexable

Mira cuatro cosas, como Googlebot:
  - código HTTP (401/403 = protegido con contraseña o por IP)
  - cabecera X-Robots-Tag con noindex
  - <meta name="robots"> o <meta name="googlebot"> con noindex
  - robots.txt: si Googlebot puede rastrear la URL
Ojo: un Disallow en robots.txt no impide indexar (la URL puede salir sin contenido)
y además impide que Google vea el noindex. Por eso cuenta como «bloqueo de rastreo», no de indexación.
Sale con código 0 si todas las URLs están como se esperaba y 1 si alguna no.
"""
import argparse
import re
import sys
import threading
import urllib.error
import urllib.request
import urllib.robotparser
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from urllib.parse import urlsplit

sys.stdout.reconfigure(encoding="utf-8")

UA = "Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)"
META = re.compile(r"<meta[^>]+name=[\"'](?:robots|googlebot)[\"'][^>]*>", re.I)


def pedir(url):
    peticion = urllib.request.Request(url, headers={"User-Agent": UA})
    try:
        with urllib.request.urlopen(peticion, timeout=20) as r:
            return r.status, r.headers, r.read(500_000).decode("utf-8", "replace")
    except urllib.error.HTTPError as e:
        return e.code, e.headers, ""


def puede_rastrear(url):
    partes = urlsplit(url)
    robots = f"{partes.scheme}://{partes.netloc}/robots.txt"
    codigo, _, texto = pedir(robots)
    if codigo >= 400:
        return True  # sin robots.txt (4xx) Google rastrea todo
    rp = urllib.robotparser.RobotFileParser()
    rp.parse(texto.splitlines())
    return rp.can_fetch("Googlebot", url)


def revisar(url):
    codigo, cabeceras, html = pedir(url)
    hallazgos = []
    if codigo in (401, 403):
        hallazgos.append(f"HTTP {codigo}: protegido")
    xrt = ", ".join(cabeceras.get_all("X-Robots-Tag") or [])
    if "noindex" in xrt.lower():
        hallazgos.append(f"X-Robots-Tag: {xrt}")
    for meta in META.findall(html):
        if "noindex" in meta.lower():
            hallazgos.append(f"meta: {meta}")
    rastreable = puede_rastrear(url)
    if not rastreable:
        hallazgos.append("robots.txt: Disallow para Googlebot (bloquea rastreo, no indexación)")
    bloqueo_real = any(not h.startswith("robots.txt") for h in hallazgos)
    return codigo, hallazgos, bloqueo_real, rastreable


def informe(url, esperado):
    codigo, hallazgos, bloqueado, rastreable = revisar(url)
    print(f"== {url}  (HTTP {codigo})")
    for h in hallazgos or ["sin bloqueos"]:
        print(f"   {h}")
    if esperado == "staging":
        ok = bloqueado
        veredicto = "bloqueado: bien para pruebas" if ok else "INDEXABLE: el entorno de pruebas puede acabar en Google"
        if ok and not rastreable and codigo not in (401, 403):
            veredicto += " (pero el Disallow impide que Google vea el noindex: mejor quitarlo o usar contraseña)"
    else:
        ok = not hallazgos and codigo == 200
        veredicto = "indexable: bien para producción" if ok else "BLOQUEADO: el bloqueo de pruebas ha llegado a producción"
    print(f"   -> {'OK' if ok else 'FALLO'}: {veredicto}")
    return ok


CASOS = {
    "/prod/": (200, {}, "<title>Producción</title>", "produccion"),
    "/staging-cabecera/": (200, {"X-Robots-Tag": "noindex, nofollow"}, "<title>Pruebas</title>", "staging"),
    "/staging-meta/": (200, {}, '<meta name="robots" content="noindex"><title>Pruebas</title>', "staging"),
    "/staging-clave/": (401, {"WWW-Authenticate": 'Basic realm="staging"'}, "", "staging"),
    "/privado/": (200, {}, '<meta name="robots" content="noindex"><title>Pruebas</title>', "staging"),
    "/olvidado/": (200, {}, '<meta name="robots" content="noindex,follow"><title>Lanzada</title>', "produccion"),
}
ROBOTS = "User-agent: *\nDisallow: /privado/\n"


class Servidor(BaseHTTPRequestHandler):
    def do_GET(self):
        if self.path == "/robots.txt":
            codigo, cabeceras, cuerpo = 200, {"Content-Type": "text/plain"}, ROBOTS
        else:
            codigo, cabeceras, cuerpo, _ = CASOS.get(self.path, (404, {}, "", None))
        self.send_response(codigo)
        for k, v in {"Content-Type": "text/html; charset=utf-8", **cabeceras}.items():
            self.send_header(k, v)
        self.end_headers()
        self.wfile.write(cuerpo.encode())

    def log_message(self, *args):
        pass


def prueba():
    servidor = ThreadingHTTPServer(("127.0.0.1", 8133), Servidor)
    threading.Thread(target=servidor.serve_forever, daemon=True).start()
    aciertos = 0
    for ruta, (*_, esperado) in CASOS.items():
        ok = informe(f"http://127.0.0.1:8133{ruta}", esperado)
        # /olvidado/ es el fallo a propósito: el script tiene que detectarlo
        aciertos += ok != (ruta == "/olvidado/")
    servidor.shutdown()
    print(f"\n{aciertos}/{len(CASOS)} casos detectados como se esperaba")
    return 0 if aciertos == len(CASOS) else 1


def main():
    p = argparse.ArgumentParser()
    p.add_argument("--prueba", action="store_true")
    p.add_argument("--esperado", choices=["staging", "produccion"])
    p.add_argument("urls", nargs="*")
    a = p.parse_args()
    if a.prueba:
        return prueba()
    if not a.esperado or not a.urls:
        p.error("hace falta --esperado y al menos una URL (o --prueba)")
    resultados = [informe(u, a.esperado) for u in a.urls]
    print(f"\n{sum(resultados)}/{len(resultados)} URLs como se esperaba")
    return 0 if all(resultados) else 1


if __name__ == "__main__":
    sys.exit(main())
