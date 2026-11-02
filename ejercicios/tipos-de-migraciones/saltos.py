"""Sigue las redirecciones de una URL salto a salto y avisa de lo que perjudica una migración.

Uso:
  python saltos.py                  revisa los casos de la clase (omicrono, uncomo, bricomart)
  python saltos.py URL [URL...]     revisa URLs concretas

Para cada URL imprime cada salto (código y Location) y avisa de:
  - 302/303/307: redirección temporal en un cambio que es permanente
  - más de un salto: cadena de redirecciones
  - la ruta no se conserva: todo va a la portada (Google lo trata como soft 404)
Sale con código 0 si todas las URLs responden y 1 si alguna falla.
"""
import sys
import urllib.error
import urllib.request
from urllib.parse import urljoin, urlsplit

sys.stdout.reconfigure(encoding="utf-8")

EJEMPLOS = [
    "http://omicrono.com/",
    "https://omicrono.elespanol.com/",
    "https://www.mundodeportivo.com/uncomo/salud/",
    "https://www.bricomart.es/herramientas.html",
]
MAX_SALTOS = 10
UA = "Mozilla/5.0 (saltos.py; ejercicio de migraciones)"


class SinRedirecciones(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args, **kwargs):
        return None


abrir = urllib.request.build_opener(SinRedirecciones).open


def un_salto(url):
    peticion = urllib.request.Request(url, method="HEAD", headers={"User-Agent": UA})
    try:
        with abrir(peticion, timeout=20) as r:
            return r.status, None
    except urllib.error.HTTPError as e:
        destino = e.headers.get("Location")
        return e.code, urljoin(url, destino) if destino else None


def saltos(url):
    cadena = []
    while len(cadena) < MAX_SALTOS:
        codigo, destino = un_salto(url)
        cadena.append((codigo, url, destino))
        if destino is None:
            break
        url = destino
    return cadena


def avisos(cadena):
    redirecciones = [s for s in cadena if s[2]]
    lista = []
    if any(c in (302, 303, 307) for c, _, _ in redirecciones):
        lista.append("redirección temporal en un cambio permanente (usar 301/308)")
    if len(redirecciones) > 1:
        lista.append(f"cadena de {len(redirecciones)} redirecciones (dejar una sola)")
    origen, final = urlsplit(cadena[0][1]).path, urlsplit(cadena[-1][1]).path
    if redirecciones and origen not in ("", "/") and final in ("", "/"):
        lista.append("la ruta se pierde: todo a la portada")
    return lista


def main(urls):
    fallos = 0
    for url in urls:
        print(f"== {url}")
        try:
            cadena = saltos(url)
        except (urllib.error.URLError, TimeoutError) as e:
            print(f"   FALLO: {e}")
            fallos += 1
            continue
        for codigo, origen, destino in cadena:
            print(f"   {codigo}  {origen}" + (f"\n        -> {destino}" if destino else ""))
        lista = avisos(cadena)
        for aviso in lista:
            print(f"   ! {aviso}")
        if not lista:
            print("   OK: " + ("un salto, permanente, ruta conservada" if len(cadena) > 1 else "sin redirección"))
    print(f"\n{len(urls) - fallos}/{len(urls)} URLs revisadas")
    return 1 if fallos else 0


if __name__ == "__main__":
    sys.exit(main(sys.argv[1:] or EJEMPLOS))
