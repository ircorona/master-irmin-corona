# Importancia de los Enlaces (Internal Linking)
**Class:** Enlazado Interno — Importancia de los Enlaces

## Why Internal Links Matter — 6 Key Functions

### 1. Facilitan la navegación
Internal links help users move between pages naturally.
```
Homepage → Services → SEO → Contact
User can navigate logically without using the back button.
```

### 2. Ayudan a establecer jerarquía de la información
Links define which pages are more important through structure.
```
Homepage (top level)
    ├── /services/          ← second level
    │   ├── /seo/           ← third level
    │   └── /web-design/    ← third level
    └── /blog/              ← second level
        └── /post-1/        ← third level
```
Pages higher in the hierarchy receive more authority and are crawled more frequently.

### 3. Ayudan a distribuir link equity (Link Juice)
Every internal link passes a portion of the linking page's authority to the target page.
```
Homepage (100% authority)
    ├── /services/        ← receives ~33% from homepage
    │   ├── /seo/         ← receives from /services/
    │   └── /web-design/  ← receives from /services/
    └── /blog/            ← receives ~33% from homepage
        ├── /post-1/      ← receives from /blog/
        └── /post-2/      ← receives from /blog/
```
The more internal links pointing to a page, the more authority it accumulates.

### 4. Afectan a qué páginas pueden ser rastreadas e indexadas por Google
```
Googlebot lands on Homepage
    ↓
Follows internal links to discover pages
    ↓
No internal links = orphan page = Googlebot may NEVER find it
```
Connects directly to the Rastreo class — internal links ARE the crawl path.

### 5. Señal sobre importancia de las páginas
The number of internal links pointing to a page tells Google how important it is.
```
/registro-marca   ← 15 internal links pointing here = HIGH importance
/legal-notice     ← 1 internal link (footer only)   = LOW importance
```
Google uses this signal to prioritize indexing and ranking.

### 6. Ayudan a evitar la canibalización
Cannibalization = two pages competing for the same keyword.
Internal links with consistent anchor text tell Google **which page is the main one** for a topic.
```html
<!-- Always link to the SAME page for "registro de marca" -->
<a href="/registro-marca">registro de marca</a>

<!-- DON'T link sometimes to /registro-marca and sometimes to /blog/como-registrar-marca
     for the same keyword — that confuses Google -->
```

## Types of Internal Links

| Type | Location | Example |
|------|----------|---------|
| **Navigation** | Header/footer menu | Main menu links |
| **Breadcrumbs** | Top of page | Home > Services > SEO |
| **Contextual** | Within body content | "Learn more about [registro de marca](/registro-marca)" |
| **Related posts** | Bottom of articles | "You might also like..." |
| **Sidebar** | Side widgets | Popular posts, categories |
| **Footer** | Page footer | Sitemap links, legal pages |

## Best Practices

### Use descriptive anchor text
```html
<!-- Good: keyword-rich anchor -->
<a href="/registro-marca">registro de marca en México</a>

<!-- Bad: generic anchor -->
<a href="/registro-marca">click aquí</a>

<!-- Bad: naked URL -->
<a href="/registro-marca">https://yoursite.com/registro-marca</a>
```

### Every page reachable in 3 clicks or less
```
Good:  Homepage → Category → Article  (3 clicks)
Bad:   Homepage → ... → ... → ... → ... → Article  (6 clicks)
```

### Don't nofollow internal links
```html
<!-- Never do this — authority is wasted, not redistributed -->
<a href="/my-page" rel="nofollow">My Page</a>
```

### Use a logical hierarchy (silo structure)
Pages within the same topic cluster should link to each other. This creates **topical silos** that Google understands.

## How to Audit Internal Linking

### Screaming Frog:
1. Crawl your site
2. **Inlinks** tab → pages with 0 = orphans (fix immediately)
3. **Outlinks** tab → pages with too many/few links
4. **Crawl Depth** → pages deeper than 3 levels need more links

### Google Search Console:
1. **Links** → **Internal links**
2. Compare most-linked pages vs your most important pages

## Connection to Previous Classes

- **URL Ruta** — Internal links use clean URLs (`/registro-marca` not `/registro-marca.php`)
- **Rastreo** — Internal links directly affect crawl budget and page discovery
- **Buenas Prácticas** — Anchor text should follow URL keyword rules
- **Hash** — Internal links can target specific sections (`/page#section`)
