"""Cuánto pesa cada librería popular: descarga el fichero de producción de
jsDelivr y lo mide tal cual y comprimido con gzip (lo que viaja por la red).

Uso: python peso.py
"""

import gzip
import sys
import urllib.request

LIBRERIAS = [
    ("jQuery 3.7.1", ["jquery@3.7.1/dist/jquery.min.js"]),
    ("React 19.2 + React DOM", ["react@19.2.0/cjs/react.production.js",
                                "react-dom@19.2.0/cjs/react-dom-client.production.js"]),
    ("Vue 3.5", ["vue@3.5.22/dist/vue.global.prod.js"]),
    ("Alpine 3.15", ["alpinejs@3.15.0/dist/cdn.min.js"]),
    ("Bootstrap 5.3 (JS + CSS)", ["bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js",
                                  "bootstrap@5.3.8/dist/css/bootstrap.min.css"]),
]


def main():
    sys.stdout.reconfigure(encoding="utf-8")
    print(f"{'Librería':26} {'Sin comprimir':>14} {'gzip':>9}")
    for nombre, ficheros in LIBRERIAS:
        crudo = comprimido = 0
        for f in ficheros:
            datos = urllib.request.urlopen(f"https://cdn.jsdelivr.net/npm/{f}", timeout=30).read()
            crudo += len(datos)
            comprimido += len(gzip.compress(datos, 9))
        print(f"{nombre:26} {crudo / 1024:11.1f} KB {comprimido / 1024:6.1f} KB")


if __name__ == "__main__":
    main()
