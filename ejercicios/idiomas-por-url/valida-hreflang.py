# -*- coding: utf-8 -*-
"""
Valida el hreflang de las dos versiones del ejercicio.

Comprueba las cinco reglas del modulo de SEO internacional:

  1. Cada pagina se declara a si misma (autorreferencial).
  2. Su hreflang propio apunta a su propia URL.
  3. Existe x-default.
  4. Reciprocidad: la hermana devuelve el apuntado ("does not link back").
  5. El canonical es distinto en cada version y apunta a si misma,
     mientras el bloque hreflang es identico en las dos.

Uso:
    python valida-hreflang.py [ruta/al/php.exe]

Por defecto busca el PHP de Laragon. Devuelve 0 si todo esta bien, 1 si no.
"""

import io
import os
import re
import subprocess
import sys
import tempfile

PHP_POR_DEFECTO = r"C:\laragon\bin\php\php-8.5.7-nts-Win32-vs17-x64\php.exe"

AQUI = os.path.dirname(os.path.abspath(__file__))
PAGINAS = [
    os.path.join(AQUI, "index.php"),
    os.path.join(AQUI, "en", "index.php"),
]

fallos = []


def chk(cond, texto):
    print(("  OK    " if cond else "  FALLA ") + texto)
    if not cond:
        fallos.append(texto)


def renderiza(php, fichero):
    salida = subprocess.check_output([php, fichero], cwd=os.path.dirname(fichero))
    return salida.decode("utf-8", "replace")


def analiza(html):
    return {
        "lang": re.search(r'<html lang="([^"]+)"', html).group(1),
        "canonical": re.search(r'rel="canonical" href="([^"]+)"', html).group(1),
        "alts": dict(
            re.findall(r'rel="alternate" hreflang="([^"]+)" href="([^"]+)"', html)
        ),
    }


def main():
    php = sys.argv[1] if len(sys.argv) > 1 else PHP_POR_DEFECTO
    if not os.path.exists(php):
        print("No encuentro PHP en: %s" % php)
        print("Pasalo como argumento: python valida-hreflang.py C:\\ruta\\php.exe")
        return 2

    datos = [analiza(renderiza(php, f)) for f in PAGINAS]
    por_url = dict((d["canonical"], d) for d in datos)

    for d in datos:
        print("\n== %s  (lang=%s)" % (d["canonical"], d["lang"]))
        reales = dict((k, v) for k, v in d["alts"].items() if k != "x-default")

        chk(d["canonical"] in d["alts"].values(),
            "se declara a si misma (autorreferencial)")
        chk(reales.get(d["lang"]) == d["canonical"],
            'el hreflang="%s" apunta a su propia URL' % d["lang"])
        chk("x-default" in d["alts"],
            "tiene x-default -> %s" % d["alts"].get("x-default"))
        chk(re.match(r"^[a-z]{2}(-[A-Za-z]{2})?$", d["lang"]) is not None,
            'el lang "%s" tiene forma ISO valida' % d["lang"])

        for codigo, url in reales.items():
            if url in por_url:
                chk(por_url[url]["alts"].get(codigo) == url,
                    'reciprocidad: %s devuelve el apuntado para hreflang="%s"'
                    % (url, codigo))

    print("\n== conjunto")
    a, b = sorted(datos, key=lambda d: d["canonical"])
    chk(a["alts"] == b["alts"], "ambas paginas emiten el MISMO bloque hreflang")
    chk(a["canonical"] != b["canonical"], "cada pagina tiene su PROPIO canonical")

    print("\n>>> " + ("TODO CORRECTO" if not fallos
                      else "HAY %d FALLO(S)" % len(fallos)))
    return 0 if not fallos else 1


if __name__ == "__main__":
    sys.exit(main())
