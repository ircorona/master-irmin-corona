"""Mini auditor SEO en Python, solo con la librería estándar.

Pide cada URL como lo haría un bot sin JavaScript y saca lo que un SEO mira
primero: código, title, description, canonical, robots, h1, hreflang y enlaces.

Uso:
    python auditor.py https://auramip.com/ [más URLs...]
    python auditor.py --csv informe.csv https://auramip.com/
"""

import csv
import sys
import urllib.error
import urllib.request
from html.parser import HTMLParser
from urllib.parse import urljoin, urlparse

AGENTE = "Mozilla/5.0 (compatible; auditor-master/1.0)"


class Extractor(HTMLParser):
    """Recorre el HTML una vez y guarda las etiquetas que interesan."""

    def __init__(self):
        super().__init__()
        self.title = ""
        self.en_title = False
        self.en_h1 = False
        self.description = None
        self.canonical = None
        self.robots = None
        self.h1 = []
        self.hreflang = []
        self.enlaces = []

    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if tag == "title":
            self.en_title = True
        elif tag == "h1":
            self.en_h1 = True
            self.h1.append("")
        elif tag == "meta" and (a.get("name") or "").lower() == "description":
            self.description = a.get("content", "")
        elif tag == "meta" and (a.get("name") or "").lower() == "robots":
            self.robots = a.get("content", "")
        elif tag == "link" and "canonical" in (a.get("rel") or "").lower().split():
            self.canonical = a.get("href")
        elif tag == "link" and a.get("hreflang"):
            self.hreflang.append(a["hreflang"])
        elif tag == "a" and a.get("href"):
            self.enlaces.append(a["href"])

    def handle_endtag(self, tag):
        if tag == "title":
            self.en_title = False
        elif tag == "h1":
            self.en_h1 = False

    def handle_data(self, data):
        if self.en_title:
            self.title += data
        if self.en_h1:
            self.h1[-1] += data


def auditar(url):
    peticion = urllib.request.Request(url, headers={"User-Agent": AGENTE})
    try:
        with urllib.request.urlopen(peticion, timeout=20) as r:
            codigo, final = r.status, r.url
            html = r.read().decode(r.headers.get_content_charset() or "utf-8", "replace")
            x_robots = r.headers.get("X-Robots-Tag")
    except urllib.error.HTTPError as e:
        return {"url": url, "codigo": e.code}

    p = Extractor()
    p.feed(html)
    dominio = urlparse(final).netloc
    internos = {urljoin(final, h).split("#")[0] for h in p.enlaces
                if urlparse(urljoin(final, h)).netloc == dominio}
    return {
        "url": url,
        "codigo": codigo,
        "url_final": final,
        "title": p.title.strip(),
        "title_len": len(p.title.strip()),
        "description_len": len(p.description) if p.description is not None else None,
        "canonical": p.canonical,
        "canonical_propio": p.canonical == final if p.canonical else None,
        "robots": p.robots or x_robots,
        "h1": len(p.h1),
        "hreflang": len(p.hreflang),
        "enlaces_internos": len(internos),
    }


def main(args):
    # En Windows la consola usa cp1252 y los acentos salen como «�»
    sys.stdout.reconfigure(encoding="utf-8")
    destino = None
    if args[:1] == ["--csv"]:
        destino, args = args[1], args[2:]
    if not args:
        sys.exit(__doc__)

    filas = [auditar(u) for u in args]
    for f in filas:
        print(f"\n{f['url']}")
        for clave, valor in f.items():
            if clave != "url":
                print(f"  {clave:17} {valor}")

    if destino:
        with open(destino, "w", newline="", encoding="utf-8") as fh:
            w = csv.DictWriter(fh, fieldnames=sorted({k for f in filas for k in f}))
            w.writeheader()
            w.writerows(filas)
        print(f"\nCSV: {destino} ({len(filas)} filas)")

    # Sale con 1 si alguna URL no responde 200
    return 0 if all(f["codigo"] == 200 for f in filas) else 1


if __name__ == "__main__":
    sys.exit(main(sys.argv[1:]))
