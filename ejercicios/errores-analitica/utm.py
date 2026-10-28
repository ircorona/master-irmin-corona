"""Construye y revisa URLs con parámetros UTM, con las reglas de la ayuda de GA4.

Uso:
  python utm.py                              revisa las URLs de ejemplo (casos buenos y malos)
  python utm.py revisar URL [URL...]         revisa URLs concretas
  python utm.py crear URL fuente medio campana [contenido]

Reglas comprobadas:
  - utm_source, utm_medium y utm_campaign presentes (si faltan, GA4 muestra "(not set)")
  - todo en minúsculas: GA4 distingue "Meta" de "meta" y fragmenta el informe
  - sin espacios ni caracteres que haya que codificar: se usan guiones o guiones bajos
  - nada de UTM en enlaces internos (mismo dominio que la web): pisan la procedencia real
"""
import re
import sys
from urllib.parse import parse_qsl, urlencode, urlsplit, urlunsplit

DOMINIO_PROPIO = "climbthesearches.com"
OBLIGATORIOS = ("utm_source", "utm_medium", "utm_campaign")


def crear(url, fuente, medio, campana, contenido=None):
    partes = urlsplit(url)
    query = [(k, v) for k, v in parse_qsl(partes.query) if not k.startswith("utm_")]
    utm = {"utm_source": fuente, "utm_medium": medio, "utm_campaign": campana}
    if contenido:
        utm["utm_content"] = contenido
    query += [(k, re.sub(r"\s+", "-", v.strip().lower())) for k, v in utm.items()]
    return urlunsplit(partes._replace(query=urlencode(query)))


def revisar(url, origen_interno=False):
    """Devuelve la lista de problemas. origen_interno=True si el enlace está en la propia web."""
    partes = urlsplit(url)
    params = dict(parse_qsl(partes.query, keep_blank_values=True))
    utm = {k: v for k, v in params.items() if k.startswith("utm_")}
    problemas = []
    if not utm:
        return problemas
    for k in OBLIGATORIOS:
        if not utm.get(k):
            problemas.append(f"falta {k} -> saldrá '(not set)'")
    for k, v in utm.items():
        if v != v.lower():
            problemas.append(f"{k}='{v}' tiene mayúsculas -> GA4 lo separa de '{v.lower()}'")
        if re.search(r"\s", v):
            problemas.append(f"{k}='{v}' tiene espacios")
    if origen_interno and partes.hostname and partes.hostname.endswith(DOMINIO_PROPIO):
        problemas.append("UTM en un enlace interno -> sobrescribe la procedencia real de la visita")
    return problemas


EJEMPLOS = [  # (url, enlace dentro de la propia web, número de problemas esperado)
    ("https://climbthesearches.com/blog/?utm_source=newsletter&utm_medium=email&utm_campaign=octubre-2026", False, 0),
    ("https://climbthesearches.com/blog/?utm_source=Newsletter&utm_medium=email&utm_campaign=octubre-2026", False, 1),
    ("https://climbthesearches.com/blog/?utm_source=linkedin&utm_campaign=octubre-2026", False, 1),
    ("https://climbthesearches.com/blog/?utm_source=linkedin&utm_medium=social&utm_campaign=Black Friday", False, 2),
    ("https://climbthesearches.com/contacto/?utm_source=home&utm_medium=banner&utm_campaign=cta", True, 1),
    ("https://climbthesearches.com/blog/", False, 0),
]


def comprobar():
    fallos = 0
    for url, interno, esperado in EJEMPLOS:
        problemas = revisar(url, interno)
        ok = len(problemas) == esperado
        fallos += not ok
        print(f"{'OK   ' if ok else 'FALLO'} {url}{'  [enlace interno]' if interno else ''}")
        for p in problemas:
            print(f"        - {p}")
    construida = crear("https://climbthesearches.com/blog/?page=2", "LinkedIn", "social", "Black Friday 2026")
    esperada = ("https://climbthesearches.com/blog/?page=2&utm_source=linkedin&utm_medium=social"
                "&utm_campaign=black-friday-2026")
    ok = construida == esperada and not revisar(construida)
    fallos += not ok
    print(f"{'OK   ' if ok else 'FALLO'} crear() normaliza: {construida}")
    total = len(EJEMPLOS) + 1
    print(f"\n{total - fallos}/{total} comprobaciones OK")
    return 1 if fallos else 0


def main(args):
    if not args:
        return comprobar()
    if args[0] == "revisar":
        malos = 0
        for url in args[1:]:
            problemas = revisar(url)
            malos += bool(problemas)
            print(("OK   " if not problemas else "MAL  ") + url)
            for p in problemas:
                print(f"        - {p}")
        return 1 if malos else 0
    if args[0] == "crear" and len(args) >= 5:
        print(crear(*args[1:6]))
        return 0
    print(__doc__)
    return 2


if __name__ == "__main__":
    sys.stdout.reconfigure(encoding="utf-8")
    sys.exit(main(sys.argv[1:]))
