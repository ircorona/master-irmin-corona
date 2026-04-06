# Errores Comunes en el Enlazado (Common Linking Mistakes)
**Class:** Enlazado Interno — Errores Comunes en el Enlazado

## 9 Common Internal Linking Mistakes

### 1. Enlaces Rotos (Broken Links)

Links that point to pages that don't exist (return 404).

```html
<!-- BROKEN: page was deleted or URL changed -->
<a href="/old-service-page">Our Services</a>  → 404 Not Found
```

**Problems:**
- Wastes crawl budget — Googlebot follows the link and gets a 404
- Wastes link equity — authority flows into a dead page
- Bad user experience — users hit a dead end
- Screaming Frog flags these as errors

**Fix:** Redirect the old URL to the new one with 301, or update the link.

### 2. Enlaces Redirigidos (Redirected Links)

Links that point to a URL that redirects to another URL.

```
User clicks: /old-page → 301 redirect → /new-page
```

```html
<!-- BAD: links to a URL that redirects -->
<a href="/old-services">Services</a>  → 301 → /services/

<!-- GOOD: link directly to the final URL -->
<a href="/services/">Services</a>
```

**Problems:**
- Slower page load (extra HTTP request)
- Small loss of link equity through the redirect chain
- Wastes crawl budget

**Fix:** Update all internal links to point directly to the final URL. Never link to a URL that redirects.

### 3. Mismos Anchors para Distintas URLs (Same Anchor Text, Different URLs)

Using the same anchor text to link to different pages.

```html
<!-- BAD: "registro de marca" points to TWO different pages -->
<a href="/registro-marca">registro de marca</a>          <!-- page A -->
<a href="/es/registro-de-marcas">registro de marca</a>   <!-- page B -->
```

**Problems:**
- Confuses Google — which page is the main one for "registro de marca"?
- Causes keyword cannibalization
- Dilutes link equity between competing pages

**Fix:** Use unique anchor text for each target URL. If two pages compete for the same keyword, consolidate them or differentiate the anchors.

### 4. Páginas con Muchos Enlaces (Too Many Outlinks/Inlinks)

Pages with excessive numbers of links.

```
Homepage with 500 outlinks → each link passes 1/500 of the authority
Homepage with 50 outlinks  → each link passes 1/50 of the authority (10x more)
```

**Problems:**
- Each additional link dilutes the equity passed to ALL other links
- Google may consider excessive links as spam
- Users get overwhelmed with too many choices

**Recommendation:** Keep links per page reasonable. No hard limit, but focus on quality over quantity.

### 5. Megamenús

Navigation menus with hundreds of links visible on every page.

```
MEGA MENU:
Products ▼
├── Category 1 (20 links)
├── Category 2 (20 links)
├── Category 3 (20 links)
└── Category 4 (20 links)
= 80+ links just in the menu, on EVERY page
```

**Problems:**
- Every page on the site passes equity to 80+ navigation links
- Dilutes equity for important pages
- Most users don't use megamenus — they use search
- Slows down page rendering

**Better approach:** Use a simpler menu with top-level categories, and let users drill down.

### 6. Enlaces Muy Cercanos entre Ellos (Links Too Close Together)

Multiple links placed too close, especially on mobile.

```html
<!-- BAD: links too close on mobile, easy to mis-tap -->
<a href="/page1">Link 1</a> <a href="/page2">Link 2</a> <a href="/page3">Link 3</a>

<!-- BETTER: adequate spacing -->
<a href="/page1" style="padding: 10px;">Link 1</a>
<a href="/page2" style="padding: 10px;">Link 2</a>
```

**Problems:**
- Mobile usability issue — Google flags this in Search Console
- Users accidentally tap the wrong link
- Increases bounce rate

**Fix:** Ensure touch targets are at least 48x48px with adequate spacing between them.

### 7. Anchor Text Excesivamente Largos (Anchor Text Too Long)

**Rule: Maximum 6-8 words for anchor text.**

```html
<!-- BAD: too long -->
<a href="/registro-marca">cómo registrar una marca comercial en México paso a paso con un abogado especializado en propiedad intelectual</a>

<!-- GOOD: 6-8 words max -->
<a href="/registro-marca">registro de marca comercial en México</a>

<!-- ALSO GOOD: shorter is fine -->
<a href="/registro-marca">registro de marca</a>
```

**Problems:**
- Google may ignore overly long anchor text
- Dilutes keyword relevance — too many words, no clear focus
- Looks spammy to users

### 8. MYTH BUSTED: Google Only Considers the First Anchor Text

**MYTH:** "If you have 2 links to the same page, Google only reads the first anchor text."

**REALITY:** This is NOT true. Google considers ALL anchor texts pointing to a page.

```html
<!-- Both anchor texts are read by Google -->
<a href="/registro-marca">registro de marca</a>           <!-- Google reads this -->
<!-- ... later on the page ... -->
<a href="/registro-marca">protege tu marca en México</a>  <!-- Google reads this too -->
```

This means you can strategically use different anchor texts for the same target URL to signal multiple keyword variations.

### 9. Separating Links with Multi-Architecture

Don't split related links across different architectures/silos just to avoid having them together on the same page.

```
BAD: Separating related links
/services/trademark/      ← links about trademarks HERE
/resources/trademark/     ← more trademark links HERE (different silo)

GOOD: Keep related content linked together
/trademark/registration/  ← all trademark links in one logical group
/trademark/cost/
/trademark/faq/
```

**If links make sense together, keep them together.** Don't force separation for the sake of "architecture" — it confuses both users and Google.

## Quick Checklist for Auditing

| Error | How to Find | How to Fix |
|-------|------------|-----------|
| Broken links | Screaming Frog → Response Codes → 404 | 301 redirect or update link |
| Redirected links | Screaming Frog → Response Codes → 3xx | Update link to final URL |
| Same anchors, different URLs | Screaming Frog → Anchor Text report | Differentiate anchor texts |
| Too many links | Screaming Frog → Outlinks count | Reduce, prioritize important links |
| Megamenus | Manual review of navigation | Simplify to top-level categories |
| Links too close | Google Search Console → Mobile Usability | Add padding, increase touch targets |
| Long anchor text | Manual review | Keep to 6-8 words max |
| Orphan anchor myth | N/A | Use multiple varied anchors strategically |

## Connection to Previous Classes

- **Importancia de los Enlaces** — Broken/redirected links waste the equity distribution
- **Tipos de Enlace** — Anchor text types (exact, partial, branded) help avoid same-anchor errors
- **Atributos de Enlaces** — Anchor text max 6-8 words applies to all link types
- **Localización de los Enlaces** — Megamenus affect crawl depth and equity distribution
- **Rastreo** — Every broken/redirected link wastes crawl budget
