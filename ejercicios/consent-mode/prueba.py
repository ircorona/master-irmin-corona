"""Qué envía GA4 a Google en cada modo de consentimiento, medido en Chrome headless.

Uso:
  python prueba.py

Sirve en local cinco páginas (generadas aquí mismo, todas con el gtag.js real y el ID de
relleno G-XXXXXXXXXX) y abre cada una en Chrome headless con registro de red. El banner
se simula: a los 2 s la página «acepta» o «rechaza».

  sin-modo         gtag.js sin modo de consentimiento (como Auramip hoy)
  basico-rechaza   modo básico: gtag.js no se carga hasta aceptar; el usuario rechaza
  basico-acepta    modo básico: el usuario acepta y entonces se carga gtag.js
  avanzado-rechaza modo avanzado: gtag.js carga con todo 'denied'; el usuario rechaza
  avanzado-acepta  modo avanzado: carga con 'denied' y el usuario acepta a los 2 s

De cada página saca las peticiones a /g/collect (con los parámetros gcs y gcd, el estado
del consentimiento que viaja en cada hit) y las cookies _ga que quedan en el navegador.
Sale con código 0 si cada modo se comporta como dice la documentación de Google.
"""
import http.server
import re
import socketserver
import subprocess
import sys
import tempfile
import threading
from pathlib import Path
from urllib.parse import parse_qs, urlsplit

sys.stdout.reconfigure(encoding="utf-8")

CHROME = r"C:\Program Files\Google\Chrome\Application\chrome.exe"
PUERTO = 8132
ID = "G-XXXXXXXXXX"

GTAG = f'<script async src="https://www.googletagmanager.com/gtag/js?id={ID}"></script>'
BASE = "window.dataLayer = window.dataLayer || []; function gtag(){dataLayer.push(arguments);}"
DENEGADO = ("gtag('consent', 'default', {ad_storage: 'denied', ad_user_data: 'denied', "
            "ad_personalization: 'denied', analytics_storage: 'denied'});")
ACEPTAR = ("gtag('consent', 'update', {ad_storage: 'granted', ad_user_data: 'granted', "
           "ad_personalization: 'granted', analytics_storage: 'granted'});")
CONFIG = f"gtag('js', new Date()); gtag('config', '{ID}');"
CARGAR_GTAG = (f"var s = document.createElement('script'); s.async = true; "
               f"s.src = 'https://www.googletagmanager.com/gtag/js?id={ID}'; document.head.appendChild(s);")
# Tras 8 s la página escribe sus cookies en el DOM para que --dump-dom las saque
VOLCADO = ("setTimeout(function(){var p = document.createElement('pre'); p.id = 'cookies'; "
           "p.textContent = document.cookie || '(ninguna)'; document.body.appendChild(p);}, 8000);")

PAGINAS = {
    "sin-modo": f"{GTAG}<script>{BASE} {CONFIG}</script>",
    "basico-rechaza": f"<script>{BASE} setTimeout(function(){{ /* rechaza: no se carga nada */ }}, 2000);</script>",
    "basico-acepta": f"<script>{BASE} setTimeout(function(){{ {ACEPTAR} {CONFIG} {CARGAR_GTAG} }}, 2000);</script>",
    "avanzado-rechaza": f"<script>{BASE} {DENEGADO}</script>{GTAG}<script>{CONFIG}</script>",
    "avanzado-acepta": f"<script>{BASE} {DENEGADO}</script>{GTAG}<script>{CONFIG} "
                       f"setTimeout(function(){{ {ACEPTAR} gtag('event', 'tras_aceptar'); }}, 2000);</script>",
}

COLLECT = re.compile(r'"(https://[a-z0-9.-]*google-analytics\.com/g/collect\?[^"]+)"')


def servir(carpeta):
    class Manejador(http.server.SimpleHTTPRequestHandler):
        def __init__(self, *a, **k):
            super().__init__(*a, directory=carpeta, **k)

        def log_message(self, *a):
            pass

    socketserver.TCPServer.allow_reuse_address = True
    servidor = socketserver.TCPServer(("127.0.0.1", PUERTO), Manejador)
    threading.Thread(target=servidor.serve_forever, daemon=True).start()
    return servidor


def medir(nombre, tmp):
    log = Path(tmp) / f"{nombre}.json"
    dom = subprocess.run(
        [CHROME, "--headless=new", "--disable-gpu", f"--user-data-dir={tmp}/perfil-{nombre}",
         f"--log-net-log={log}", "--virtual-time-budget=12000", "--dump-dom",
         f"http://127.0.0.1:{PUERTO}/{nombre}.html"],
        capture_output=True, text=True, encoding="utf-8", timeout=120,
    ).stdout
    hits = []
    for url in dict.fromkeys(COLLECT.findall(log.read_text(encoding="utf-8", errors="replace"))):
        q = {k: v[0] for k, v in parse_qs(urlsplit(url).query).items()}
        hits.append((q.get("en", "?"), q.get("gcs", "-"), q.get("gcd", "-"), q.get("cid", "-")))
    m = re.search(r'<pre id="cookies">(.*?)</pre>', dom, re.S)
    cookies = m.group(1) if m else "(sin volcado)"
    tiene_ga = bool(re.search(r"(^|; )_ga=", cookies))
    return hits, tiene_ga, cookies


def main():
    with tempfile.TemporaryDirectory() as tmp:
        for nombre, cuerpo in PAGINAS.items():
            Path(tmp, f"{nombre}.html").write_text(
                f"<!doctype html><html><head><meta charset='utf-8'><title>{nombre}</title>{cuerpo}</head>"
                f"<body><p>{nombre}</p><script>{VOLCADO}</script></body></html>", encoding="utf-8")
        servidor = servir(tmp)
        resultados = {}
        try:
            for nombre in PAGINAS:
                hits, tiene_ga, cookies = medir(nombre, tmp)
                resultados[nombre] = (hits, tiene_ga)
                print(f"== {nombre}")
                for en, gcs, gcd, cid in hits:
                    print(f"   hit {en:15} gcs={gcs:5} gcd={gcd:13} cid={cid}")
                if not hits:
                    print("   (ningún hit a Google Analytics)")
                print(f"   cookie _ga: {'sí' if tiene_ga else 'no'}   [{cookies[:70]}]")
        finally:
            servidor.shutdown()
            servidor.server_close()

    def gcs(nombre):
        return {h[1] for h in resultados[nombre][0]}

    comprobaciones = [
        ("sin modo: hay hits y cookie _ga", resultados["sin-modo"][0] and resultados["sin-modo"][1]),
        ("básico rechazado: ni un hit ni cookie", not resultados["basico-rechaza"][0] and not resultados["basico-rechaza"][1]),
        ("básico aceptado: hits con gcs=G111 y cookie", "G111" in gcs("basico-acepta") and resultados["basico-acepta"][1]),
        ("avanzado rechazado: hits sin cookies (gcs=G100) y sin _ga", gcs("avanzado-rechaza") == {"G100"} and not resultados["avanzado-rechaza"][1]),
        ("avanzado aceptado: G100 antes, G111 después y cookie", {"G100", "G111"} <= gcs("avanzado-acepta") and resultados["avanzado-acepta"][1]),
    ]
    print()
    for texto, ok in comprobaciones:
        print(f"{'OK   ' if ok else 'FALLO'} {texto}")
    bien = sum(1 for _, ok in comprobaciones if ok)
    print(f"\n{bien}/{len(comprobaciones)} comprobaciones OK")
    return 0 if bien == len(comprobaciones) else 1


if __name__ == "__main__":
    sys.exit(main())
