"""Comprueba cómo está verificado un dominio en Search Console: registro TXT de DNS y meta en la portada.

Uso:
  python txt.py                     revisa los dominios de ejemplo
  python txt.py DOMINIO [...]       revisa dominios concretos (sin https://)

Es el `host -t txt dominio` de la clase, pero con DNS sobre HTTPS (dns.google), así funciona
igual en Windows, Git Bash o Linux sin instalar nada. Por cada dominio:
  - lista los TXT y marca los de verificación (google-site-verification y los de otros servicios)
  - mira si la portada lleva <meta name="google-site-verification"> (método de propiedad de prefijo de URL)
Solo el TXT sirve para una propiedad de dominio (todas las variantes http/https y subdominios).
Sale con código 0 si todos los dominios responden y 1 si alguno falla.
"""
import json
import re
import sys
import urllib.error
import urllib.request

sys.stdout.reconfigure(encoding="utf-8")

EJEMPLOS = ["amazon.com", "auramip.com"]
DOH = "https://dns.google/resolve?name={}&type=TXT"
META = re.compile(r"<meta[^>]+name=[\"']google-site-verification[\"'][^>]*>", re.I)


def txt(dominio):
    with urllib.request.urlopen(DOH.format(dominio), timeout=20) as r:
        datos = json.load(r)
    if datos.get("Status") != 0:
        raise RuntimeError(f"DNS Status {datos.get('Status')}")
    return [a["data"].strip('"') for a in datos.get("Answer", []) if a.get("type") == 16]


def meta_portada(dominio):
    peticion = urllib.request.Request(f"https://{dominio}/", headers={"User-Agent": "Mozilla/5.0"})
    try:
        with urllib.request.urlopen(peticion, timeout=20) as r:
            return META.findall(r.read(300_000).decode("utf-8", "replace"))
    except (urllib.error.URLError, TimeoutError) as e:
        return [f"(no se pudo leer la portada: {e})"]


def tipo(registro):
    if registro.startswith("google-site-verification="):
        return "GOOGLE"
    if registro.startswith("v=spf1") or registro.startswith("spf2.0"):
        return "spf"
    if "verification" in registro.split("=")[0] or registro.startswith("MS="):
        return "verif."
    return ""


def main(dominios):
    fallos = 0
    for dominio in dominios:
        print(f"== {dominio}")
        try:
            registros = txt(dominio)
        except (urllib.error.URLError, TimeoutError, RuntimeError) as e:
            print(f"   FALLO: {e}")
            fallos += 1
            continue
        for r in sorted(registros, key=lambda x: tipo(x) != "GOOGLE"):
            print(f"   {tipo(r):7} {r[:90]}")
        google = [r for r in registros if tipo(r) == "GOOGLE"]
        metas = meta_portada(dominio)
        print(f"   -> TXT de Google: {len(google)}  (vale para propiedad de dominio)" if google
              else "   -> sin TXT de Google: no hay propiedad de dominio verificada por DNS (o se quitó el token)")
        print(f"   -> meta en la portada: {metas[0][:90]}" if metas else "   -> sin meta de verificación en la portada")
    print(f"\n{len(dominios) - fallos}/{len(dominios)} dominios revisados")
    return 1 if fallos else 0


if __name__ == "__main__":
    sys.exit(main(sys.argv[1:] or EJEMPLOS))
