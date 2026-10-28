"""Prueba las cuatro formas de cargar Clarity de la clase (y una mejorada) con Chrome headless.

Uso:  python prueba.py [ruta-a-chrome]

Sirve versiones/*.html cambiando solo el dominio de Clarity por un falso local: el
«tag» falso avisa al servidor de cuándo se cargó (ms desde el inicio de la página).
Chrome corre con tiempo virtual (--virtual-time-budget), así que esperar 5 s no tarda
5 s de verdad, y el presupuesto simula cuánto se queda el visitante en la página.
"""
import http.server
import shutil
import subprocess
import sys
import tempfile
import threading
from pathlib import Path
from urllib.parse import parse_qs, urlsplit

CHROME = sys.argv[1] if len(sys.argv) > 1 else "C:/Program Files/Google/Chrome/Application/chrome.exe"
PUERTO = 8131
DIR = Path(__file__).parent / "versiones"
CLARITY_REAL = "https://www.clarity.ms/tag/"
CLARITY_FALSO = f"http://127.0.0.1:{PUERTO}/clarity/tag/"
TAG_FALSO = """
fetch('/log?pagina=' + encodeURIComponent(location.pathname) + '&t=' + Math.round(performance.now()));
"""

UA = {
    "Chrome": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) "
              "Chrome/130.0.0.0 Safari/537.36",
    "Googlebot": "Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 "
                 "(KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36 "
                 "(compatible; Googlebot/2.1; +http://www.google.com/bot.html)",
    "Bingbot": "Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; bingbot/2.0; "
               "+http://www.bing.com/bingbot.htm) Chrome/130.0.0.0 Safari/537.36",
    "Lighthouse": "Mozilla/5.0 (Linux; Android 11; moto g power (2022)) AppleWebKit/537.36 "
                  "(KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36 Chrome-Lighthouse",
}

registro = []


class Manejador(http.server.BaseHTTPRequestHandler):
    def do_GET(self):
        url = urlsplit(self.path)
        if url.path == "/log":
            q = parse_qs(url.query)
            registro.append((q["pagina"][0], int(q["t"][0])))
            self.responder(204, b"", "text/plain")
        elif url.path.startswith("/clarity/tag/"):
            self.responder(200, TAG_FALSO.encode(), "application/javascript")
        elif (DIR / url.path.lstrip("/")).is_file():
            html = (DIR / url.path.lstrip("/")).read_text(encoding="utf-8")
            self.responder(200, html.replace(CLARITY_REAL, CLARITY_FALSO).encode(), "text/html; charset=utf-8")
        else:
            self.responder(404, b"no", "text/plain")

    def responder(self, codigo, cuerpo, tipo):
        self.send_response(codigo)
        self.send_header("Content-Type", tipo)
        self.send_header("Content-Length", str(len(cuerpo)))
        self.end_headers()
        self.wfile.write(cuerpo)

    def log_message(self, *args):
        pass


def visitar(pagina, ua="Chrome", estancia_ms=8000, perfil=None):
    """Abre la página y devuelve el ms en que cargó Clarity, o None si no cargó."""
    registro.clear()
    temporal = perfil is None
    perfil = perfil or tempfile.mkdtemp(prefix="chrome-clarity-")
    subprocess.run([CHROME, "--headless", "--disable-gpu", "--no-first-run",
                    f"--user-data-dir={perfil}", f"--user-agent={UA[ua]}",
                    f"--virtual-time-budget={estancia_ms}", "--dump-dom",
                    f"http://127.0.0.1:{PUERTO}/{pagina}"],
                   capture_output=True, timeout=60)
    if temporal:
        shutil.rmtree(perfil, ignore_errors=True)
    return registro[0][1] if registro else None


def main():
    servidor = http.server.ThreadingHTTPServer(("127.0.0.1", PUERTO), Manejador)
    threading.Thread(target=servidor.serve_forever, daemon=True).start()

    perfil_v3 = tempfile.mkdtemp(prefix="chrome-clarity-v3-")
    perfil_v4 = tempfile.mkdtemp(prefix="chrome-clarity-v4-")
    # (descripción, función que visita, comprobación sobre el ms devuelto)
    casos = [
        ("v1 directo, Chrome", lambda: visitar("v1-directo.html"),
         lambda t: t is not None and t < 5000),
        ("v1 directo, visita de 2 s", lambda: visitar("v1-directo.html", estancia_ms=2000),
         lambda t: t is not None),
        ("v2 esperar 5 s, Chrome 8 s", lambda: visitar("v2-esperar-5s.html"),
         lambda t: t is not None and t >= 5000),
        ("v2 esperar 5 s, visita de 3 s", lambda: visitar("v2-esperar-5s.html", estancia_ms=3000),
         lambda t: t is None),
        ("v3 token, 1.ª visita 3 s", lambda: visitar("v3-esperar-token.html", estancia_ms=3000, perfil=perfil_v3),
         lambda t: t is None),
        ("v3 token, 2.ª visita 3 s", lambda: visitar("v3-esperar-token.html", estancia_ms=3000, perfil=perfil_v3),
         lambda t: t is None),
        ("v3 token, 3.ª visita 8 s", lambda: visitar("v3-esperar-token.html", perfil=perfil_v3),
         lambda t: t is not None and t >= 5000),
        ("v3 token, 4.ª visita 3 s", lambda: visitar("v3-esperar-token.html", estancia_ms=3000, perfil=perfil_v3),
         lambda t: t is not None and t < 1000),
        ("v4 UA, Googlebot", lambda: visitar("v4-esperar-token-ua.html", ua="Googlebot"),
         lambda t: t is None),
        ("v4 UA, Bingbot", lambda: visitar("v4-esperar-token-ua.html", ua="Bingbot"),
         lambda t: t is not None),
        ("v4 UA, Lighthouse (PSI)", lambda: visitar("v4-esperar-token-ua.html", ua="Lighthouse"),
         lambda t: t is not None),
        ("v4 UA, Chrome 1.ª visita 8 s", lambda: visitar("v4-esperar-token-ua.html", perfil=perfil_v4),
         lambda t: t is not None and t >= 5000),
        ("v5 mejorado, Chrome", lambda: visitar("v5-mejorado.html"),
         lambda t: t is not None and t < 5000),
        ("v5 mejorado, visita de 2 s", lambda: visitar("v5-mejorado.html", estancia_ms=2000),
         lambda t: t is not None),
        ("v5 mejorado, Googlebot", lambda: visitar("v5-mejorado.html", ua="Googlebot"),
         lambda t: t is not None),
    ]
    ok = 0
    for descripcion, accion, esperado in casos:
        t = accion()
        bien = esperado(t)
        ok += bien
        cargo = f"Clarity cargó a los {t} ms" if t is not None else "Clarity NO cargó"
        print(f"{'OK   ' if bien else 'FALLO'} {descripcion:<30} {cargo}")
    servidor.shutdown()
    for p in (perfil_v3, perfil_v4):
        shutil.rmtree(p, ignore_errors=True)
    print(f"\n{ok}/{len(casos)} comprobaciones OK")
    return 0 if ok == len(casos) else 1


if __name__ == "__main__":
    sys.stdout.reconfigure(encoding="utf-8")
    sys.exit(main())
