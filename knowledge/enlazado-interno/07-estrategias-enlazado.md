# Estrategias de Enlazado (Linking Strategies)
**Class:** Enlazado Interno — Estrategias de Enlazado
**Tool:** [Asdrubal Link Analyzer](https://wordpress.org/plugins/) — Free WordPress plugin by the professor

## 9 Linking Strategies

### 1. CTAs (Call to Action)

Strategic internal links disguised as buttons/CTAs that guide users to conversion pages.

```html
<!-- CTA as internal link -->
<a href="/es/contactanos" class="btn-cta">Agenda tu Consulta Gratuita</a>

<!-- Multiple CTAs in Auramip pointing to contact page -->
"Agenda tu Consulta Gratuita"  → /es/contactanos  (from homepage)
"Obtén tu Consulta Gratuita"   → /es/contactanos  (from service pages)
"Agenda una Consulta Gratuita" → /es/contactanos  (from about page)
```

Each CTA is an internal link that passes equity AND drives conversions.

### 2. Related Content (Otros productos, artículos, etc.)

Link to related content at the bottom or sidebar of pages.

```
On /es/registro-de-marcas/:
  "Related services:"
  → /es/registro-de-patentes/
  → /es/registro-de-derechos-de-autor/

On /es/patentes-es/que-es-una-patente/:
  "Related articles:"
  → /es/propiedad-intelectual/ley-de-propiedad-industrial/
  → /es/marcas/niza/
```

Creates topic clusters and keeps users browsing.

### 3. Database Automations & Replacements

Automatically create internal links by replacing keywords in content with links via database.

```php
// Example: auto-replace "registro de marca" with a link in all posts
// Done via WordPress plugin or custom SQL
UPDATE wp_posts
SET post_content = REPLACE(
    post_content,
    'registro de marca',
    '<a href="/es/registro-de-marcas">registro de marca</a>'
)
WHERE post_content LIKE '%registro de marca%';
```

**Caution:** Only apply to body content, not titles or meta. Limit replacements per page to avoid over-optimization.

### 4. Analytics-Driven Linking

Use analytics data to identify:
- Pages with high traffic but low conversions → add CTAs
- Pages with high bounce rate → add more internal links
- Pages with 0 internal links → fix orphan pages
- Top landing pages → add links to money pages

```
Google Analytics → Behavior → Site Content → All Pages
Sort by: bounce rate DESC
→ These pages need more internal links to keep users browsing
```

### 5. Conversion Funnels (Cold to Hot)

Structure internal links to move users from informational content to transactional pages.

```
COLD (awareness):
  /es/propiedad-intelectual/que-es-la-propiedad-intelectual/
  → "¿Necesitas proteger tu marca?" → links to...

WARM (consideration):
  /es/registro-de-marcas/
  → "Consulta las tarifas IMPI" → links to...

HOT (decision):
  /es/contactanos/
  → Contact form → conversion
```

```
Funnel flow:
Blog article → Service page → Contact page
(informational)  (commercial)   (transactional)
     COLD            WARM            HOT
```

### 6. Customer Journey

Map internal links to the user's decision-making process:

```
Stage 1 - AWARENESS:   "¿Qué es una patente?" (blog)
                         ↓ internal link
Stage 2 - INTEREST:    "Ley de propiedad industrial" (educational)
                         ↓ internal link
Stage 3 - EVALUATION:  "Registro de patentes" (service page)
                         ↓ internal link (CTA)
Stage 4 - DECISION:    "Contáctanos" (conversion)
```

Every page should link to the NEXT stage in the journey, not skip stages.

### 7. Link Position = Priority (Higher = More Important)

Links higher on the page receive more weight from Google.

```html
<body>
  <!-- HIGHEST priority: first links Google encounters -->
  <header>
    <a href="/es/registro-de-marcas">Marcas</a>      ← most weight
    <a href="/es/registro-de-patentes">Patentes</a>
  </header>

  <!-- HIGH priority: early body content -->
  <main>
    <p>First paragraph with <a href="/service">link</a></p>  ← high weight

    <!-- MEDIUM priority: middle content -->
    <p>Middle content with <a href="/article">link</a></p>

    <!-- LOWER priority: late content -->
    <p>Last paragraph with <a href="/page">link</a></p>      ← less weight
  </main>

  <!-- LOWEST priority: footer links -->
  <footer>
    <a href="/privacy">Privacy</a>                            ← least weight
  </footer>
</body>
```

**Rule:** Put your most important links early in the page — both in navigation and in body content.

### 8. Link Obfuscation (Ofuscación de enlaces)

Hiding links from Google while keeping them clickable for users.

```html
<!-- Normal link: Google follows and passes equity -->
<a href="/page">Link</a>

<!-- Obfuscated: Google can't follow, no equity passed -->
<span onclick="window.location='/page'" style="cursor:pointer">Link</span>

<!-- Or via JavaScript -->
<a href="javascript:void(0)" data-url="/page" class="obfuscated">Link</a>
```

**Use cases:**
- Login/register pages you don't want indexed
- Legal pages (privacy policy, terms) that shouldn't receive equity
- Pagination links
- Duplicate filter pages

**Warning:** Use sparingly. Google is getting better at executing JavaScript.

### 9. Pages with Scroll (Cambios de páginas con Scroll)

Single-page layouts that change URL as the user scrolls, using the History API.

```javascript
// Change URL as user scrolls to different sections
window.addEventListener('scroll', function() {
    if (isInView('#services')) {
        history.replaceState(null, '', '/services');
    } else if (isInView('#about')) {
        history.replaceState(null, '', '/about');
    }
});
```

This creates virtual "pages" from a single long page — each section gets its own URL for Google to index, while the user experiences smooth scrolling.

## Asdrubal Link Analyzer — Auramip.com Analysis

From the CSV data exported by the plugin:

### Pages with Most Inlinks (best linked):
| Page | Inlinks | Status |
|------|---------|--------|
| /es/registro-de-marcas/ | 10 | ✓ Well linked |
| /es/patentes-es/que-es-una-patente/ | 7 | ✓ Good |
| /es/registro-de-patentes/ | 6 | ✓ Good |
| /es/registro-de-derechos-de-autor/ | 6 | ✓ Good |
| /es/propiedad-intelectual/denominaciones-de-origen/ | 6 | ✓ Good |
| /es/contactanos/ | 5 | ✓ Good (CTA target) |

### Pages with 0 Inlinks (ORPHANS — fix immediately):
| Page | Inlinks | Problem |
|------|---------|---------|
| /es/acerca-de/ | 0 | ✗ Only linked FROM, never linked TO |
| /es/articulos-es/ | 0 | ✗ Article index has no inlinks |
| /es/aviso-de-privacidad/ | 0 | ✗ OK for privacy (can obfuscate) |
| /es/servicios/ | 0 | ✗ Services page has no body inlinks! |

### Pages with 0 Outlinks (DEAD ENDS — fix immediately):
| Page | Outlinks | Problem |
|------|----------|---------|
| /es/aviso-de-privacidad/ | 0 | ✗ No outgoing links at all |
| /es/contactanos/ | 0 | ✗ Contact page is a dead end |
| /es/patentes-es/que-es-una-patente/ | 0 | ✗ Key article with no outlinks! |

### Anchor Text Issues:
| Issue | Example |
|-------|---------|
| Image alt text used as anchor (too long) | "Concepto visual de qué es una patente en México: bombillo con engranajes..." |
| Same anchor for different pages | "Más Información" used for 3 different service pages |
| Generic anchors | "Más Información", "Conoce Más" |

### Recommendations:
1. **Fix orphan pages** — Add links TO /es/acerca-de/, /es/articulos-es/, /es/servicios/
2. **Fix dead-end pages** — Add outlinks FROM /es/contactanos/, /es/que-es-una-patente/
3. **Shorten image alt anchors** — Keep to 6-8 words max when image is inside `<a>`
4. **Differentiate "Más Información"** — Use descriptive anchors instead of generic text
5. **Add cross-links between articles** — Blog posts only link back to home/articles index

## Connection to Previous Classes

- **Importancia de los Enlaces** — CTAs and funnels maximize link equity for conversion pages
- **Localización de los Enlaces** — Higher = more important, confirms position-based priority
- **Errores Comunes** — Orphan/dead-end pages identified by Asdrubal confirm error patterns
- **Atributos de Enlaces** — Obfuscation uses JavaScript instead of `<a>` tags to hide from Google
- **Rastreo** — Obfuscated links save crawl budget by preventing Google from crawling low-value pages
