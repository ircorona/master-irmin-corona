"""Audita el JSON-LD de una página de ecommerce contra los requisitos de Google.

Uso:
    python auditar_ld.py https://tienda.example/producto/      (URL)
    python auditar_ld.py fixtures/producto-bien.html           (fichero local)

Comprueba Product (fragmento y ficha de comerciante), Offer, envío, devoluciones,
BreadcrumbList y Organization. Errores = propiedades obligatorias; avisos =
recomendadas o incoherencias. Sale con código 1 si hay errores.
Requisitos según la documentación de Google consultada el 2026-11-14.
"""
import json
import re
import sys
import urllib.request
from html.parser import HTMLParser

UA = "Mozilla/5.0 (auditoria-datos-estructurados; ejercicio del master)"
SCHEMA = "https://schema.org/"


class LDParser(HTMLParser):
    """Recoge los bloques <script type="application/ld+json"> y si hay microdata/RDFa."""

    def __init__(self):
        super().__init__()
        self.blocks, self._in, self._buf = [], False, []
        self.microdata = self.rdfa = 0

    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if "itemtype" in a:
            self.microdata += 1
        if "typeof" in a:
            self.rdfa += 1
        if tag == "script" and (a.get("type") or "").strip().lower() == "application/ld+json":
            self._in, self._buf = True, []

    def handle_endtag(self, tag):
        if tag == "script" and self._in:
            self.blocks.append("".join(self._buf))
            self._in = False

    def handle_data(self, data):
        if self._in:
            self._buf.append(data)


def cargar(origen):
    if re.match(r"https?://", origen):
        req = urllib.request.Request(origen, headers={"User-Agent": UA})
        with urllib.request.urlopen(req, timeout=20) as r:
            return r.read().decode(r.headers.get_content_charset() or "utf-8", "replace")
    with open(origen, encoding="utf-8") as f:
        return f.read()


def nodos(obj):
    """Aplana @graph, listas y objetos anidados: devuelve todos los dicts con @type."""
    if isinstance(obj, list):
        for x in obj:
            yield from nodos(x)
    elif isinstance(obj, dict):
        if "@type" in obj:
            yield obj
        for k, v in obj.items():
            if k != "@context":
                yield from nodos(v)


def tipos(n):
    t = n.get("@type", [])
    return [x.split("/")[-1] for x in (t if isinstance(t, list) else [t])]


def lista(v):
    return v if isinstance(v, list) else ([] if v is None else [v])


class Informe:
    def __init__(self):
        self.lineas, self.errores, self.avisos = [], 0, 0

    def error(self, donde, msg):
        self.errores += 1
        self.lineas.append(f"  ERROR  [{donde}] {msg}")

    def aviso(self, donde, msg):
        self.avisos += 1
        self.lineas.append(f"  AVISO  [{donde}] {msg}")

    def ok(self, donde, msg):
        self.lineas.append(f"  ok     [{donde}] {msg}")


def precio(offer):
    """Devuelve (precio, moneda) de price o de priceSpecification."""
    p, c = offer.get("price"), offer.get("priceCurrency")
    for spec in lista(offer.get("priceSpecification")):
        if isinstance(spec, dict) and "priceType" not in spec:
            p = p if p is not None else spec.get("price")
            c = c or spec.get("priceCurrency")
    return p, c


def revisar_offer(o, inf, i):
    d = f"Offer {i}"
    p, c = precio(o)
    if p is None:
        inf.error(d, "falta price (o priceSpecification.price)")
    else:
        if isinstance(p, str) and not re.fullmatch(r"\d+(\.\d+)?", p.strip()):
            inf.error(d, f"price no es un número limpio: {p!r} (sin símbolo, punto decimal)")
        else:
            try:
                if float(p) <= 0:
                    inf.aviso(d, "price = 0: vale para fragmento, no para ficha de comerciante (exige > 0)")
            except ValueError:
                pass
    if not c:
        inf.error(d, "falta priceCurrency (obligatoria en ficha de comerciante)")
    elif not re.fullmatch(r"[A-Z]{3}", str(c)):
        inf.error(d, f"priceCurrency no es ISO 4217 de 3 letras: {c!r}")
    av = o.get("availability")
    if not av:
        inf.aviso(d, "sin availability (recomendada)")
    elif not str(av).startswith(("https://schema.org/", "http://schema.org/")):
        inf.aviso(d, f"availability sin URL de schema.org: {av!r} (usa https://schema.org/InStock)")
    for k in ("itemCondition", "priceValidUntil", "url"):
        if k not in o:
            inf.aviso(d, f"sin {k} (recomendada)")
    for s in lista(o.get("shippingDetails")):
        if isinstance(s, dict):
            for k in ("shippingRate", "shippingDestination", "deliveryTime"):
                if k not in s:
                    inf.error(d, f"shippingDetails sin {k} (obligatoria si hay envío marcado)")
    for r in lista(o.get("hasMerchantReturnPolicy")):
        if isinstance(r, dict):
            for k in ("applicableCountry", "returnPolicyCategory"):
                if k not in r:
                    inf.error(d, f"hasMerchantReturnPolicy sin {k}")
            if str(r.get("returnPolicyCategory", "")).endswith("MerchantReturnFiniteReturnWindow") \
                    and "merchantReturnDays" not in r:
                inf.error(d, "plazo de devolución finito sin merchantReturnDays")


def revisar_product(n, inf, org_tiene_politicas):
    nombre = n.get("name", "(sin nombre)")
    d = f"Product {nombre!r}"
    if "name" not in n:
        inf.error(d, "falta name")
    if not any(k in n for k in ("offers", "review", "aggregateRating")):
        inf.error(d, "fragmento de producto: necesita offers, review o aggregateRating")
    if "image" not in n:
        inf.error(d, "falta image (obligatoria en ficha de comerciante)")
    for k in ("description", "brand", "sku"):
        if k not in n:
            inf.aviso(d, f"sin {k} (recomendada)")
    if not any(k in n for k in ("gtin", "gtin8", "gtin12", "gtin13", "gtin14", "mpn")):
        inf.aviso(d, "sin GTIN ni mpn (recomendados para identificar el producto)")
    ar = n.get("aggregateRating")
    if isinstance(ar, dict):
        if "ratingValue" not in ar or not any(k in ar for k in ("ratingCount", "reviewCount")):
            inf.error(d, "aggregateRating necesita ratingValue y ratingCount o reviewCount")
        inf.aviso(d, "hay aggregateRating: comprobar que las reseñas se ven en la página")
    offers = []
    for o in lista(n.get("offers")):
        if isinstance(o, dict) and "AggregateOffer" in tipos(o):
            inf.aviso(d, "AggregateOffer: vale para fragmento; la ficha de comerciante pide Offer")
            if "lowPrice" not in o:
                inf.error(d, "AggregateOffer sin lowPrice")
        elif isinstance(o, dict):
            offers.append(o)
    for i, o in enumerate(offers, 1):
        revisar_offer(o, inf, i)
    if offers and not org_tiene_politicas:
        if not any("shippingDetails" in o for o in offers):
            inf.aviso(d, "sin shippingDetails ni política de envío en Organization")
        if not any("hasMerchantReturnPolicy" in o for o in offers):
            inf.aviso(d, "sin hasMerchantReturnPolicy ni política de devoluciones en Organization")
    inf.ok(d, f"{len(offers)} oferta(s) revisada(s)")


def revisar_breadcrumb(n, inf):
    d = "BreadcrumbList"
    items = lista(n.get("itemListElement"))
    if len(items) < 2:
        inf.error(d, f"necesita al menos 2 ListItem (tiene {len(items)})")
    pos = []
    for i, it in enumerate(items):
        if not isinstance(it, dict):
            continue
        if "position" not in it:
            inf.error(d, f"ListItem {i + 1} sin position")
        else:
            pos.append(int(it["position"]))
        ultimo = i == len(items) - 1
        if "item" not in it and not ultimo:
            inf.error(d, f"ListItem {i + 1} sin item (solo el último puede omitirlo)")
        item = it.get("item")
        nombre = it.get("name") or (item.get("name") if isinstance(item, dict) else None)
        if not nombre:
            inf.error(d, f"ListItem {i + 1} sin name")
    if pos and pos != list(range(1, len(pos) + 1)):
        inf.aviso(d, f"posiciones no correlativas desde 1: {pos}")
    inf.ok(d, f"{len(items)} elementos")


def revisar_org(n, inf):
    d = f"{tipos(n)[0]} {n.get('name', '')!r}".strip()
    for k in ("name", "url", "logo"):
        if k not in n:
            inf.aviso(d, f"sin {k} (recomendada; Organization no tiene obligatorias)")
    if "aggregateRating" in n or "review" in n:
        inf.aviso(d, "reseñas sobre la propia organización: no optan a estrellas (reseñas autoatribuidas)")
    inf.ok(d, "revisada")


ORG_TIPOS = {"Organization", "OnlineStore", "Store", "LocalBusiness", "Corporation"}


def auditar(origen):
    html = cargar(origen)
    p = LDParser()
    p.feed(html)
    inf = Informe()
    print(f"\n== {origen}")
    print(f"   bloques JSON-LD: {len(p.blocks)} · microdata: {p.microdata} · RDFa: {p.rdfa}")
    if p.microdata or p.rdfa:
        inf.aviso("formato", "hay microdata/RDFa: Google recomienda JSON-LD y este script solo lee JSON-LD")
    datos = []
    for i, b in enumerate(p.blocks, 1):
        try:
            datos.append(json.loads(b))
        except json.JSONDecodeError as e:
            inf.error(f"bloque {i}", f"JSON no válido: {e.msg} (línea {e.lineno})")
    todos = [n for d in datos for n in nodos(d)]
    org_politicas = any(set(tipos(n)) & ORG_TIPOS and
                        ("hasMerchantReturnPolicy" in n or "hasShippingService" in n) for n in todos)
    vistos = {}
    for n in todos:
        t = set(tipos(n))
        if "Product" in t:
            revisar_product(n, inf, org_politicas)
        elif "BreadcrumbList" in t:
            revisar_breadcrumb(n, inf)
        elif t & ORG_TIPOS:
            revisar_org(n, inf)
        for x in t:
            vistos[x] = vistos.get(x, 0) + 1
    print("   tipos: " + (", ".join(f"{k}×{v}" for k, v in sorted(vistos.items())) or "ninguno"))
    if not any("Product" in tipos(n) for n in todos):
        inf.aviso("página", "sin Product: si es una ficha de producto, no opta a fragmento ni ficha de comerciante")
    print("\n".join(inf.lineas))
    print(f"   -> {inf.errores} errores, {inf.avisos} avisos")
    return inf.errores


if __name__ == "__main__":
    sys.stdout.reconfigure(encoding="utf-8")  # la consola de Windows no es UTF-8 por defecto
    if len(sys.argv) < 2:
        sys.exit(__doc__)
    total = 0
    for origen in sys.argv[1:]:
        try:
            total += auditar(origen)
        except Exception as e:  # una URL caída no para las demás
            print(f"\n== {origen}\n  ERROR  no se pudo leer: {e}")
            total += 1
    sys.exit(1 if total else 0)
