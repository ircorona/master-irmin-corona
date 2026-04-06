# Localización de los Enlaces (Link Placement)
**Class:** Enlazado Interno — Localización de los Enlaces

## 5 Key Factors of Link Placement

### 1. Crawl Depth (Número de clicks desde la home)

```
Depth 0:  Homepage                    ← most authority
Depth 1:  /services/, /blog/          ← 1 click from home
Depth 2:  /services/seo/              ← 2 clicks from home
Depth 3:  /blog/post-123/             ← 3 clicks from home (maximum recommended)
Depth 4+: /blog/2026/04/05/post/      ← too deep, Google may not crawl
```

**Rule: 3 clicks is optimal.** Every page should be reachable in 3 clicks or less from the homepage.

The deeper a page is, the:
- Less authority it receives
- Less frequently Googlebot crawls it
- Harder it is for users to find

### 2. Recurrencia (Recurrence)

How many times a link appears across the site:

```
Header/footer links:  Appear on EVERY page  → high recurrence → high authority
Contextual links:     Appear on 1 page      → low recurrence  → but highest SEO weight per link
Sidebar links:        Appear on many pages   → medium recurrence
```

**Balance:** Recurrence builds authority through volume, but contextual links (in body content) carry the most individual weight.

### 3. Anchor Text

The clickable text of the link tells Google what the target page is about.

**Class example — PcComponentes:**
The entire product card (image + title + price + rating) is wrapped in ONE `<a>` tag:

```html
<a href="https://www.pccomponentes.com/pccom-lite-amd-ryzen-5-8400f..."
   title="PcCom Lite AMD Ryzen 5 8400F / 16GB / 1TB SSD / RTX 5060 8GB V2"
   data-product-name="PcCom Lite AMD Ryzen 5 8400F..."
   data-product-price="1069"
   data-product-brand="PcCom"
   data-product-category="Sobremesa">
   
   <!-- Everything inside is the "anchor" -->
   <img alt="PcCom Lite AMD Ryzen 5 8400F..." src="...">
   <h3>PcCom Lite AMD Ryzen 5 8400F / 16GB / 1TB SSD / RTX 5060 8GB V2</h3>
   <span>1.069€</span>
   <span>4,6/5 - 35 opiniones</span>
</a>
```

**What Google reads as anchor text:**
- The `title` attribute
- The `alt` text of the image
- The visible text (h3, price, reviews)
- All `data-*` attributes for analytics tracking

**Key insight:** The ENTIRE clickable area goes to ONE link. This concentrates all the anchor text signals to a single target URL.

### 4. Páginas Huérfanas (Orphan Pages)

Pages with 0 internal links pointing to them:

```
Homepage → /services/ → /seo/
Homepage → /blog/     → /post-1/
                         /post-2/  ← has internal links ✓

/secret-page/           ← NO internal links pointing here = ORPHAN ✗
```

**Problems:**
- Googlebot may never discover them
- They receive 0 internal link equity
- They appear in sitemap but not in site structure
- Users can't navigate to them

**How to find orphan pages:**
- Screaming Frog → Crawl → check pages with 0 inlinks
- Google Search Console → Coverage → indexed pages not in your crawl

### 5. Páginas sin Salida (Dead-End Pages)

Pages with 0 outgoing links (no links to other pages):

```
/thank-you-page/     ← only says "Thanks!" with no links = dead end
/pdf-download/       ← no navigation, no links
/404-page/           ← if it has no links back to the site
```

**Problems:**
- Users get stuck with no way to continue browsing
- Link equity flows IN but never flows OUT (wasted)
- Increases bounce rate
- Googlebot stops crawling at this page

**Fix:** Always include at least:
- Navigation (header/footer)
- Related content links
- A CTA or "back to home" link

## Auramip.com Crawl Tree Analysis

From the Screaming Frog crawl tree:

```
auramip.com (root)
├── patent-registration-mexico      ← Depth 1
│   └── es/registro-de-patentes     ← Depth 2
│       └── es/patentes-es/que-es-una-patente  ← Depth 3 ✓ (maximum)
├── copyright-registration-mexico   ← Depth 1
│   └── es/registro-de-derechos-de-autor       ← Depth 2
├── patents/what-is-a-patent        ← Depth 1
├── es/                             ← Depth 1 (Spanish hub)
│   ├── servicios                   ← Depth 2
│   ├── registro-de-marcas          ← Depth 2
│   ├── contactanos                 ← Depth 2
│   ├── articulos-es                ← Depth 2
│   │   ├── category/marcas         ← Depth 3 ✓
│   │   ├── category/propiedad-intelectual  ← Depth 3 ✓
│   │   ├── category/patentes-es    ← Depth 3 ✓
│   │   └── category/derechos-de-autor      ← Depth 3 ✓
│   ├── denominaciones-de-origen    ← Depth 2
│   │   └── indicaciones-geograficas ← Depth 3 ✓
│   └── aviso-de-privacidad         ← Depth 2
│       └── privacy-notice          ← Depth 3 ✓
├── blog                            ← Depth 1
│   ├── category/patents            ← Depth 2
│   └── category/intellectual-property ← Depth 2
├── services                        ← Depth 1
├── contact-us                      ← Depth 1
├── trademark-registration-mexico   ← Depth 1
├── about                           ← Depth 1
└── patents/google-patents          ← Depth 1
```

### Findings:
- **Maximum depth: 3** ✓ (all pages within 3 clicks)
- **Structure is well organized** — EN pages at root, ES pages under /es/
- **Category pages at depth 3** — could benefit from more internal links to reduce effective depth

### Areas to improve:
- **Cross-linking between EN and ES** — patent pages should link to their equivalent language
- **Contextual body links** — most links are navigation-only, add in-content links
- **Blog posts** — need more internal links from/to service pages
- **hreflang implementation** — important for the bilingual structure

## Connection to Previous Classes

- **Importancia de los Enlaces** — Link placement determines how equity flows
- **Tipos de Enlace** — Contextual links (body) > navigation links (header) > footer links
- **URL Relativas y Absolutas** — All internal links should use `/path` (relative from root)
- **Atributos de Enlaces** — Anchor text is one of the 5 key factors covered here
- **Rastreo** — Crawl depth directly affects crawl budget allocation
