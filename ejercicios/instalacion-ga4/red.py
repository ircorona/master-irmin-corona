"""Detecta cómo está instalado GA4 en una web, mirando las peticiones reales del navegador.

Uso:
  python red.py                     revisa las webs de ejemplo
  python red.py URL [URL...]        revisa webs concretas

Abre cada URL en Chrome headless con registro de red (--log-net-log), espera 15 s
de tiempo virtual y lista los scripts pedidos a googletagmanager.com:
  - solo gtm.js            -> GTM optimizado (desde el 12-10-2026 el contenedor envía a GA4
                              sin gtag.js), o GA4 bloqueado hasta el consentimiento, o sin GA4
  - gtm.js + gtag/js G-... -> GTM con etiqueta de Google clásica (contenedor sin optimizar)
  - solo gtag/js G-...     -> etiqueta nativa o plugin
  - nada                   -> sin GA4 en el navegador (o detrás del banner de cookies)
Lo que esté bloqueado hasta aceptar cookies no aparece: headless no acepta el banner.
Sale con código 0 si Chrome ha cargado todas las webs.
"""
import re
import subprocess
import sys
import tempfile
from pathlib import Path

sys.stdout.reconfigure(encoding="utf-8")

CHROME = r"C:\Program Files\Google\Chrome\Application\chrome.exe"
EJEMPLOS = ["https://www.simoahava.com/", "https://auramip.com/"]
PATRON = re.compile(r"https://www\.googletagmanager\.com/(gtm\.js|gtag/js)\?id=([A-Z0-9-]+)")


def peticiones(url):
    with tempfile.TemporaryDirectory() as tmp:
        log = Path(tmp) / "net.json"
        subprocess.run(
            [CHROME, "--headless=new", "--disable-gpu", f"--user-data-dir={tmp}/perfil",
             f"--log-net-log={log}", "--virtual-time-budget=15000", "--dump-dom", url],
            capture_output=True, timeout=90,
        )
        if not log.exists():
            return None
        return sorted(set(PATRON.findall(log.read_text(encoding="utf-8", errors="replace"))))


def diagnostico(encontrados):
    gtm = [i for s, i in encontrados if s == "gtm.js"]
    gtag = [i for s, i in encontrados if s == "gtag/js" and i.startswith("G-")]
    if gtm and gtag:
        return "GTM con etiqueta de Google clásica (gtm.js + gtag.js)"
    if gtm:
        return "Solo gtm.js: contenedor optimizado, GA4 tras el consentimiento o sin GA4 (mirar /g/collect)"
    if gtag:
        return "Etiqueta nativa o plugin (solo gtag.js)"
    return "Sin scripts de Google en el navegador"


def main(urls):
    fallos = 0
    for url in urls:
        encontrados = peticiones(url)
        print(f"== {url}")
        if encontrados is None:
            print("   FALLO: Chrome no generó el registro de red")
            fallos += 1
            continue
        for script, ident in encontrados:
            print(f"   {script:8} {ident}")
        print(f"   -> {diagnostico(encontrados)}")
    print(f"\n{len(urls) - fallos}/{len(urls)} webs revisadas")
    return 1 if fallos else 0


if __name__ == "__main__":
    sys.exit(main(sys.argv[1:] or EJEMPLOS))
