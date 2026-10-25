# Python para SEO: un mini auditor

Ejercicio de **Frameworks y Tecnologías, clase 06 Otros lenguajes en SEO**.

Python apenas mueve webs (1,1 % según W3Techs), pero es el lenguaje de las herramientas de SEO: rastrear, sacar datos de Search Console, cruzar CSV. Este script es el ejemplo mínimo: pide URLs como un bot sin JavaScript y saca lo que un SEO mira primero. Solo usa la librería estándar (nada de `pip install`).

## Cómo probarlo

Git Bash (Python 3.11):

```bash
cd ejercicios/python-seo
python auditor.py https://auramip.com/ https://sanchezdonate.net/ https://auramip.com/no-existe-xyz/
python auditor.py --csv informe.csv https://auramip.com/     # además, a CSV
```

Salida real (2026-10-25):

```
https://auramip.com/
  codigo            200
  url_final         https://auramip.com/
  title             Auramip | Intellectual Property Law Firm in Mexico
  title_len         50
  description_len   129
  canonical         https://auramip.com/
  canonical_propio  True
  robots            index, follow, max-image-preview:large
  h1                1
  hreflang          4
  enlaces_internos  16

https://sanchezdonate.net/
  codigo            200
  url_final         https://sanchezdonate.net/
  title             El SEO Técnico - Carlos Sánchez
  title_len         31
  description_len   119
  canonical         https://sanchezdonate.net/
  canonical_propio  True
  robots            all, notranslate, max-image-preview:standard
  h1                1
  hreflang          2
  enlaces_internos  55

https://auramip.com/no-existe-xyz/
  codigo            404
```

Sale con 1 si alguna URL no responde 200 (en el ejemplo, la tercera), así que sirve en un cron o un CI.

## Qué enseña

- **`html.parser` basta para lo básico.** Una clase que hereda de `HTMLParser` recorre el HTML una vez y guarda `title`, metas, `canonical`, `hreflang`, `h1` y enlaces. Para más (selectores CSS, HTML roto) se usa BeautifulSoup o lxml.
- **Lo que ve es lo que ve un bot sin JS.** Como `curl`: el HTML del servidor, no el DOM.
- **`enlaces_internos` cuenta URLs distintas del mismo dominio**, sin el `#`. Por eso Auramip da 16 aunque tenga 54 `<a href>` (menú, pie y tarjetas repiten destinos, y hay externos).
- **Los acentos en la consola de Windows.** Sin `sys.stdout.reconfigure(encoding="utf-8")`, «Técnico» salía como «T�cnico»: el HTML se descodificaba bien, la consola (cp1252) no.
