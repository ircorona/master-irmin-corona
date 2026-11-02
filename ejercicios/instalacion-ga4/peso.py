"""Mide lo que descarga el navegador con cada método de instalación de GA4.

Uso:
  python peso.py

Descarga en vivo los dos scripts de googletagmanager.com con IDs de relleno
(Google sirve el código base aunque el ID no exista):
  - gtag.js  (/gtag/js?id=G-...): la etiqueta de Google, lo que pega el método nativo
  - gtm.js   (/gtm.js?id=GTM-...): el contenedor de Tag Manager, vacío

Con GTM y una etiqueta de Google dentro, el navegador descarga los dos: primero
gtm.js y después gtag.js (comprobado con red.py en simoahava.com).
Sale con código 0 si las dos descargas responden 200 y GTM pesa más que el nativo.
"""
import os
import subprocess
import sys

sys.stdout.reconfigure(encoding="utf-8")

SCRIPTS = {
    "gtag.js": "https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX",
    "gtm.js": "https://www.googletagmanager.com/gtm.js?id=GTM-XXXXXXX",
}
METODOS = {
    "Etiqueta nativa (gtag.js en el <head>)": ["gtag.js"],
    "GTM con etiqueta de Google dentro": ["gtm.js", "gtag.js"],
    "Plugin que inserte gtag.js (p. ej. Site Kit)": ["gtag.js"],
}


def curl(url, encoding):
    """Devuelve (estado, bytes transferidos). Usa curl porque Google sirve Brotli y urllib no lo decodifica."""
    salida = subprocess.run(
        ["curl", "-s", "-o", os.devnull, "-w", "%{http_code} %{size_download}",
         "-H", f"Accept-Encoding: {encoding}", "-H", "User-Agent: Mozilla/5.0", url],
        capture_output=True, text=True, timeout=60, check=True,
    ).stdout.split()
    return int(salida[0]), int(salida[1])


def descargar(url):
    estado, red = curl(url, "br, gzip")  # lo que pide Chrome
    _, js = curl(url, "identity")        # sin comprimir: lo que el navegador tiene que analizar
    return estado, red, js


def main():
    medidas = {}
    for nombre, url in SCRIPTS.items():
        estado, red, js = descargar(url)
        medidas[nombre] = (red, js)
        print(f"{nombre:8} HTTP {estado}  transferido {red / 1024:6.1f} KB (Brotli)  JS {js / 1024:6.1f} KB  {url}")
        if estado != 200:
            print(f"FALLO: {nombre} respondió {estado}")
            return 1

    print("\nPor método")
    totales = {}
    for metodo, piezas in METODOS.items():
        red = sum(medidas[p][0] for p in piezas)
        js = sum(medidas[p][1] for p in piezas)
        totales[metodo] = red
        print(f"  {metodo:40} {' + '.join(piezas):18} {red / 1024:6.1f} KB transferidos, {js / 1024:6.1f} KB de JS")

    nativo = totales["Etiqueta nativa (gtag.js en el <head>)"]
    gtm = totales["GTM con etiqueta de Google dentro"]
    print(f"\nGTM añade {(gtm - nativo) / 1024:.1f} KB transferidos (+{(gtm / nativo - 1) * 100:.0f} %) frente al nativo,"
          " con el contenedor vacío. Cada etiqueta que se añada a GTM suma más.")
    ok = gtm > nativo
    print("OK" if ok else "FALLO: GTM no pesa más que el nativo")
    return 0 if ok else 1


if __name__ == "__main__":
    sys.exit(main())
