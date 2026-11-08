"""Mide qué URLs de imagen pone next/image en el HTML con cuatro configuraciones.

Copia las fuentes a %TEMP%/next-img (fuera de OneDrive), instala Next con npm la primera vez,
genera dos JPEG con Pillow, hace `next build` + `next start` en el puerto 8136 y comprueba el
src y el srcset de cada página. Fase 2: build aparte con el loader pasado desde un componente
de servidor, que debe fallar.

Uso (Git Bash):  python prueba.py
"""
import html
import os
import re
import shutil
import subprocess
import sys
import tempfile
import time
import urllib.error
import urllib.request
from pathlib import Path

from PIL import Image

sys.stdout.reconfigure(encoding="utf-8")
AQUI = Path(__file__).resolve().parent
OBRA = Path(tempfile.gettempdir()) / "next-img"
PUERTO = 8136
BASE = f"http://127.0.0.1:{PUERTO}"
NEXT = ["node", "node_modules/next/dist/bin/next"]
ENV = {**os.environ, "NEXT_TELEMETRY_DISABLED": "1"}
ORIGINAL = "/img/gato-naranja.jpg"


def preparar():
    """Copia las fuentes (sin node_modules) y genera las imágenes de prueba."""
    OBRA.mkdir(exist_ok=True)
    for viejo in ("app", ".next"):
        shutil.rmtree(OBRA / viejo, ignore_errors=True)
    shutil.copytree(AQUI / "app", OBRA / "app")
    for f in ("package.json", "next.config.mjs", "loader-carlos.js"):
        shutil.copy2(AQUI / f, OBRA / f)
    (OBRA / "public" / "img").mkdir(parents=True, exist_ok=True)
    for nombre, color, tam in (("gato-naranja", (230, 120, 30), (1600, 1200)),
                               ("perro-marron", (120, 80, 40), (3200, 2400))):
        im = Image.new("RGB", tam, color)
        for x in range(0, tam[0], 40):  # rayas para que el JPEG no sea trivial
            im.paste((255, 255, 255), (x, 0, x + 8, tam[1]))
        im.save(OBRA / "public" / "img" / f"{nombre}.jpg", quality=85)
    if not (OBRA / "node_modules" / "next").exists():
        print("npm install (solo la primera vez)...")
        subprocess.run([shutil.which("npm"), "install", "--no-audit", "--no-fund"],
                       cwd=OBRA, check=True, env=ENV)


def build():
    r = subprocess.run(NEXT + ["build"], cwd=OBRA, env=ENV, capture_output=True,
                       text=True, encoding="utf-8", errors="replace")
    return r.returncode, r.stdout + r.stderr


def get(url, accept="*/*"):
    req = urllib.request.Request(url, headers={"Accept": accept, "User-Agent": "prueba-imagenes"})
    try:
        with urllib.request.urlopen(req, timeout=30) as resp:
            return resp.status, resp.headers.get("Content-Type", ""), resp.read()
    except urllib.error.HTTPError as e:
        return e.code, e.headers.get("Content-Type", ""), b""


def imgs(page_html):
    """Devuelve [(src, [urls del srcset])] de cada <img> del HTML."""
    out = []
    for tag in re.findall(r"<img\b[^>]*>", page_html):
        attrs = {k.lower(): html.unescape(v) for k, v in re.findall(r'(\w+)="([^"]*)"', tag)}
        srcset = [p.strip().split(" ")[0] for p in attrs.get("srcset", "").split(",") if p.strip()]
        out.append((attrs.get("src", ""), srcset))
    return out


def main():
    preparar()
    resultados = []

    def check(nombre, ok, detalle=""):
        resultados.append(ok)
        print(f"  [{'OK' if ok else 'FALLO'}] {nombre}{'  -> ' + detalle if detalle else ''}")

    print("\nFase 1: next build con las cuatro variantes")
    code, salida = build()
    check("next build termina con exit 0", code == 0, f"exit {code}")
    if code != 0:
        print(salida[-3000:])
        return 1

    server = subprocess.Popen(NEXT + ["start", "-p", str(PUERTO)], cwd=OBRA, env=ENV,
                              stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
    try:
        for _ in range(60):
            try:
                if get(BASE + "/")[0] == 200:
                    break
            except OSError:
                time.sleep(0.5)
        paginas = {}
        for ruta in ("/por-defecto", "/loader-carlos", "/override-src", "/sin-optimizar"):
            st, _, body = get(BASE + ruta, "text/html")
            paginas[ruta] = imgs(body.decode("utf-8"))
            print(f"\n{ruta} (HTTP {st})")
            for src, srcset in paginas[ruta]:
                print(f"  src    = {src}")
                print(f"  srcset = {len(srcset)} URLs" + (f", la primera {srcset[0]}" if srcset else ""))

        print("\nComprobaciones")
        src, srcset = paginas["/por-defecto"][0]
        check("por defecto: src va por /_next/image con parámetros", src.startswith("/_next/image?url="), src)
        check("por defecto: todo el srcset va por /_next/image", bool(srcset) and all(u.startswith("/_next/image?") for u in srcset))

        (s1, ss1), (s2, ss2) = paginas["/loader-carlos"]
        check("loader de Carlos, imagen 1: src = original", s1 == ORIGINAL, s1)
        check("loader de Carlos, imagen 2 (sizes, srcset largo): src = original", s2 == "/img/perro-marron.jpg", s2)
        check("loader de Carlos: srcset sigue optimizado", all(u.startswith("/_next/image?") for u in ss1 + ss2), f"{len(ss1)} + {len(ss2)} URLs")

        src, srcset = paginas["/override-src"][0]
        check("overrideSrc: src = original", src == ORIGINAL, src)
        check("overrideSrc: srcset sigue optimizado", bool(srcset) and all(u.startswith("/_next/image?") for u in srcset))

        src, srcset = paginas["/sin-optimizar"][0]
        check("unoptimized: src = original y sin srcset", src == ORIGINAL and not srcset, f"src={src} srcset={len(srcset)}")

        print("\nQué devuelve el servidor")
        st, ct, body = get(BASE + ORIGINAL)
        check("la original responde 200 image/jpeg", st == 200 and ct.startswith("image/jpeg"), f"{st} {ct} {len(body)} B")
        opt = paginas["/por-defecto"][0][0]
        st1, ct1, b1 = get(BASE + opt, "image/avif,image/webp,*/*")
        st2, ct2, b2 = get(BASE + opt, "image/jpeg,*/*")
        check("/_next/image: la MISMA URL da WebP o JPEG según Accept",
              st1 == st2 == 200 and "webp" in ct1 and "jpeg" in ct2, f"{ct1} {len(b1)} B / {ct2} {len(b2)} B")
    finally:
        server.terminate()
        server.wait(timeout=20)

    print("\nFase 2: el loader pasado desde un componente de servidor")
    destino = OBRA / "app" / "loader-en-servidor"
    destino.mkdir()
    shutil.copy2(AQUI / "extra" / "loader-en-servidor.js", destino / "page.js")
    code, salida = build()
    motivo = next((l.strip() for l in salida.splitlines() if "Functions cannot be passed" in l or "Client Component" in l), "")
    check("next build falla (exit distinto de 0)", code != 0, f"exit {code}")
    check("el error es el de pasar una función a un componente de cliente", bool(motivo), motivo[:160])
    shutil.rmtree(destino)

    ok = sum(resultados)
    print(f"\n{ok}/{len(resultados)} comprobaciones OK")
    return 0 if ok == len(resultados) else 1


if __name__ == "__main__":
    sys.exit(main())
