"""Revisa una plantilla contra la lista del briefing SEO: etiquetas del <head> y del <body>.

Uso:
  python plantilla.py                  revisa las páginas de ejemplo
  python plantilla.py URL [URL...]     revisa URLs concretas

Por cada URL saca lo que el briefing pide definir, sin JavaScript (lo que ve Google en el HTML inicial):
  <head>  title, meta description, meta robots, canonical, hreflang (autorreferencia y x-default),
          amphtml, meta http-equiv, lang, viewport
  <body>  etiquetas HTML5 (header, nav, main, article, section, aside, footer), h1 y saltos de nivel,
          imágenes sin alt, enlaces internos y enlaces sin href, breadcrumbs y tipos de Schema (JSON-LD)
Marca con «!» lo que no cumple las buenas prácticas.
Sale con código 0 si todas las URLs se han podido descargar y 1 si alguna falla.
"""
import json
import sys
import urllib.error
import urllib.request
from html.parser import HTMLParser
from urllib.parse import urljoin, urlsplit

sys.stdout.reconfigure(encoding="utf-8")

EJEMPLOS = ["https://auramip.com/", "https://auramip.com/es/", "https://www.elespanol.com/omicrono/"]
UA = "Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)"
HTML5 = ["header", "nav", "main", "article", "section", "aside", "footer"]


class Lector(HTMLParser):
    def __init__(self):
        super().__init__()
        self.en_head = True
        self.title, self.dentro_title = "", False
        self.metas, self.links, self.lang = [], [], None
        self.html5 = {t: 0 for t in HTML5}
        self.encabezados, self.imgs_sin_alt, self.imgs = [], 0, 0
        self.enlaces, self.sin_href = [], 0
        self.jsonld, self.dentro_jsonld, self.buffer = [], False, ""

    def handle_starttag(self, tag, attrs):
        a = {k: (v or "") for k, v in attrs}
        if tag == "html":
            self.lang = a.get("lang")
        elif tag == "body":
            self.en_head = False
        elif tag == "title" and self.en_head:
            self.dentro_title = True
        elif tag == "meta":
            self.metas.append(a)
        elif tag == "link":
            self.links.append(a)
        elif tag in self.html5:
            self.html5[tag] += 1
        elif tag in ("h1", "h2", "h3", "h4", "h5", "h6"):
            self.encabezados.append(int(tag[1]))
        elif tag == "img":
            self.imgs += 1
            self.imgs_sin_alt += "alt" not in a
        elif tag == "a":
            if a.get("href"):
                self.enlaces.append(a["href"])
            else:
                self.sin_href += 1
        elif tag == "script" and a.get("type") == "application/ld+json":
            self.dentro_jsonld, self.buffer = True, ""

    def handle_endtag(self, tag):
        if tag == "title":
            self.dentro_title = False
        elif tag == "script" and self.dentro_jsonld:
            self.jsonld.append(self.buffer)
            self.dentro_jsonld = False

    def handle_data(self, data):
        if self.dentro_title:
            self.title += data
        if self.dentro_jsonld:
            self.buffer += data


def tipos_schema(bloques):
    tipos = []

    def recorrer(nodo):
        if isinstance(nodo, dict):
            t = nodo.get("@type")
            tipos.extend(t if isinstance(t, list) else [t] if t else [])
            for v in nodo.values():
                recorrer(v)
        elif isinstance(nodo, list):
            for v in nodo:
                recorrer(v)

    for b in bloques:
        try:
            recorrer(json.loads(b))
        except json.JSONDecodeError:
            tipos.append("(JSON-LD inválido)")
    return sorted(set(tipos))


def meta(lector, nombre):
    for m in lector.metas:
        if m.get("name", "").lower() == nombre:
            return m.get("content", "")
    return None


def revisar(url):
    peticion = urllib.request.Request(url, headers={"User-Agent": UA})
    with urllib.request.urlopen(peticion, timeout=20) as r:
        final, html = r.geturl(), r.read().decode("utf-8", "replace")
    p = Lector()
    p.feed(html)
    lineas = []

    def fila(etiqueta, valor, mal=False):
        lineas.append(f"   {'!' if mal else ' '} {etiqueta:18} {valor}")

    lineas.append("  <head>")
    titulo = " ".join(p.title.split())
    fila("title", f"{len(titulo)} car. «{titulo[:70]}»", not titulo or len(titulo) > 65)
    desc = meta(p, "description")
    fila("meta description", f"{len(desc)} car." if desc else "falta", not desc)
    robots = meta(p, "robots")
    fila("meta robots", robots or "(no hay: indexable por defecto)", bool(robots and "noindex" in robots.lower()))
    canon = [l.get("href") for l in p.links if "canonical" in l.get("rel", "").lower().split()]
    if not canon:
        fila("canonical", "falta", True)
    else:
        absoluta = urljoin(final, canon[0])
        fila("canonical", absoluta + ("" if absoluta == final else f"  (no es la propia: {final})"), len(canon) > 1)
    alternos = [l for l in p.links if l.get("rel", "").lower() == "alternate" and l.get("hreflang")]
    if alternos:
        idiomas = [l["hreflang"] for l in alternos]
        propio = any(urljoin(final, l.get("href", "")) == final for l in alternos)
        fila("hreflang", f"{', '.join(idiomas)}" + ("" if propio else "  (sin autorreferencia)"), not propio)
        if "x-default" not in idiomas:
            fila("", "sin x-default (recomendable, no obligatorio)")
    else:
        fila("hreflang", "no hay")
    amp = [l.get("href") for l in p.links if l.get("rel", "").lower() == "amphtml"]
    fila("amphtml", amp[0] if amp else "no hay")
    equiv = [f"{m.get('http-equiv')}={m.get('content', '')[:40]}" for m in p.metas if m.get("http-equiv")]
    fila("meta http-equiv", "; ".join(equiv) if equiv else "no hay")
    fila("lang", p.lang or "falta", not p.lang)
    fila("viewport", meta(p, "viewport") or "falta", not meta(p, "viewport"))

    lineas.append("  <body>")
    faltan = [t for t in ("header", "nav", "main", "footer") if not p.html5[t]]
    fila("HTML5", " ".join(f"{t}:{n}" for t, n in p.html5.items()), bool(faltan))
    h1 = p.encabezados.count(1)
    saltos = sum(1 for a, b in zip(p.encabezados, p.encabezados[1:]) if b > a + 1)
    fila("h1", f"{h1}", h1 != 1)
    fila("encabezados", f"{len(p.encabezados)} (saltos de nivel: {saltos})", saltos > 0)
    fila("imágenes sin alt", f"{p.imgs_sin_alt} de {p.imgs}", p.imgs_sin_alt > 0)
    host = urlsplit(final).netloc
    internos = [h for h in p.enlaces if urlsplit(urljoin(final, h)).netloc == host]
    fila("enlaces internos", f"{len(internos)} (<a> sin href: {p.sin_href})", p.sin_href > 0)
    tipos = tipos_schema(p.jsonld)
    fila("breadcrumbs", "BreadcrumbList" if "BreadcrumbList" in tipos else "sin BreadcrumbList en JSON-LD")
    fila("Schema", ", ".join(tipos) if tipos else "no hay JSON-LD")
    return final, lineas


def main(urls):
    fallos = 0
    for url in urls:
        print(f"== {url}")
        try:
            final, lineas = revisar(url)
        except (urllib.error.URLError, TimeoutError) as e:
            print(f"   FALLO: {e}")
            fallos += 1
            continue
        if final != url:
            print(f"   (redirige a {final})")
        print("\n".join(lineas))
    print(f"\n{len(urls) - fallos}/{len(urls)} URLs revisadas")
    return 1 if fallos else 0


if __name__ == "__main__":
    sys.exit(main(sys.argv[1:] or EJEMPLOS))
