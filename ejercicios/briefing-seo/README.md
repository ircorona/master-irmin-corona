# Briefing SEO: revisar una plantilla contra la lista

Ejercicio de la clase **Migraciones 06-01 · Briefing SEO**. El briefing fija qué tiene que llevar cada plantilla en el `<head>` y en el `<body>`; en QA y el día del lanzamiento hay que comprobar que la plantilla maquetada lo lleva. Este script saca esa lista de una URL.

| Fichero | Qué hace |
|---|---|
| `plantilla.py` | Descarga el HTML (sin JavaScript, como Googlebot en el primer paso) y lista title, meta description, meta robots, canonical, hreflang, amphtml, meta http-equiv, `lang`, viewport, etiquetas HTML5, h1 y saltos de encabezado, imágenes sin `alt`, enlaces internos, `<a>` sin `href`, breadcrumbs y tipos de Schema en JSON-LD. Marca con `!` lo que no cumple |

Solo librería estándar de Python.

## Ejecutar

```bash
python plantilla.py                            # auramip.com, /es/ y elespanol.com/omicrono/
python plantilla.py https://www.ejemplo.com/   # cualquier plantilla
```

Sale con código 0 si todas las URLs se han podido descargar y 1 si alguna falla.

## Resultado (Python 3.11, 2026-11-02)

```
== https://auramip.com/
  <head>
     title              50 car. «Auramip | Intellectual Property Law Firm in Mexico»
     meta description   129 car.
     meta robots        index, follow, max-image-preview:large
     canonical          https://auramip.com/
     hreflang           en, es-MX, es, x-default
     amphtml            no hay
     meta http-equiv    no hay
     lang               en
     viewport           width=device-width, initial-scale=1.0
  <body>
     HTML5              header:1 nav:1 main:1 article:3 section:8 aside:0 footer:1
     h1                 1
     encabezados        19 (saltos de nivel: 0)
     imágenes sin alt   0 de 10
   ! enlaces internos   46 (<a> sin href: 2)
     breadcrumbs        sin BreadcrumbList en JSON-LD
     Schema             Answer, Attorney, Country, FAQPage, GeoCoordinates, LegalService, OpeningHoursSpecification, Organization, PostalAddress, Question, Thing, WebSite
== https://auramip.com/es/
  (igual que la inglesa: title 47 car., lang es-MX, mismos hreflang, 0 imágenes sin alt, 2 <a> sin href)
== https://www.elespanol.com/omicrono/
  <head>
   ! title              112 car. «Novedades y noticias de tecnología, internet, informática, software, h»
     meta description   147 car.
     meta robots        (no hay: indexable por defecto)
     canonical          https://www.elespanol.com/omicrono/
     hreflang           no hay
     amphtml            no hay
     meta http-equiv    no hay
     lang               es
     viewport           width=device-width, initial-scale=1.0, minimum-scale=1.0
  <body>
   ! HTML5              header:1 nav:4 main:0 article:47 section:0 aside:6 footer:1
     h1                 1
     encabezados        51 (saltos de nivel: 0)
   ! imágenes sin alt   5 de 64
     enlaces internos   110 (<a> sin href: 0)
     breadcrumbs        BreadcrumbList
     Schema             BreadcrumbList, ListItem, NewsMediaOrganization, SiteNavigationElement

3/3 URLs revisadas
```

## Qué enseña

- **Auramip cumple casi toda la lista**: un h1, sin saltos de encabezado, todas las imágenes con `alt`, canonical a sí misma, hreflang con autorreferencia y `x-default`, HTML5 completo y Schema de despacho (`LegalService`, `Attorney`, `FAQPage`). Los dos `<a>` sin `href` son enlaces de Alpine.js (`x-bind:href="results[best()].href"`): el destino se calcula en el navegador, así que en el HTML no hay enlace rastreable. Si ese enlace lleva a una página que interesa posicionar, mejor un `href` real.
- **Auramip declara `es-MX` y `es` a la misma URL** (`/es/`). Es válido (una página puede servir a varios códigos) y hace que `/es/` sea también la versión para cualquier hispanohablante.
- **Omicrono, en El Español**: sin `<main>`, título de 112 caracteres y 5 imágenes sin `alt`, pero con `BreadcrumbList`. Es lo que el briefing de la migración de 2019 tendría que haber fijado para la plantilla.
- **Nadie lleva `amphtml`** en estas tres: AMP ya no da ventaja en Google (ver la nota).

## Límites

- Sin JavaScript: lo que se inyecte en el navegador no aparece. Es a propósito (es lo que el briefing debe exigir en el HTML inicial), pero no es todo lo que Google acaba viendo tras renderizar.
- Los umbrales (title de más de 65 caracteres, faltar `main`) son orientativos, no reglas de Google.
- Una URL cada vez: para comparar plantillas antes y después de una migración, pasarle una URL de cada tipo (portada, categoría, ficha, artículo).
