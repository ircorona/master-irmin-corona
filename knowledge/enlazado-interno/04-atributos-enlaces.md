# Atributos de los Enlaces (Link Attributes)
**Class:** Enlazado Interno — Atributos de los Enlaces
**Reference:** [W3Schools `<a>` tag](https://www.w3schools.com/tags/tag_a.asp)

## All `<a>` Attributes

```html
<a href="url"
   target="_blank"
   rel="nofollow noopener"
   title="tooltip text"
   download
>anchor text</a>
```

## `rel` Attribute — Relationship to target

### SEO-related values

| Value | Purpose | When to use |
|-------|---------|-------------|
| `nofollow` | Do NOT pass link equity | Links you don't want to endorse |
| `sponsored` | Marks as paid/advertisement | Affiliate links, ads, paid partnerships |
| `ugc` | User Generated Content | Comments, forum posts, user profiles |

```html
<!-- Paid link — tell Google it's sponsored -->
<a href="https://sponsor.com" rel="sponsored" target="_blank">Our sponsor</a>

<!-- User comment link — tell Google it's UGC -->
<a href="https://user-site.com" rel="ugc noopener">User's website</a>

<!-- Link you don't trust -->
<a href="https://sketchy-site.com" rel="nofollow noreferrer">Source</a>
```

**Important:** If you DON'T add `rel`, the link is `follow` by default = passes link equity. This is what you want for internal links.

### Security-related values

| Value | Purpose | When to use |
|-------|---------|-------------|
| `noopener` | Prevents new tab from accessing `window.opener` | Always with `target="_blank"` |
| `noreferrer` | Hides the referring URL from the target site | When you don't want them to know you linked |

```html
<!-- ALWAYS use noopener with _blank (security) -->
<a href="https://external.com" target="_blank" rel="noopener noreferrer">External</a>

<!-- Without noopener, the new tab can run: -->
<!-- window.opener.location = 'https://phishing-site.com' -->
<!-- This redirects YOUR original tab to a malicious site! -->
```

### Combining rel values

```html
<!-- Multiple values separated by SPACE (not comma!) -->
<a href="https://ad.com" rel="sponsored noopener noreferrer" target="_blank">Ad</a>

<!-- WRONG: comma separated -->
<a href="https://ad.com" rel="nofollow, noreferrer">Wrong syntax</a>

<!-- CORRECT: space separated -->
<a href="https://ad.com" rel="nofollow noreferrer">Correct syntax</a>
```

**Note from class:** The professor used commas in examples (`rel="nofollow, noreferer"`), but the correct HTML syntax is **space-separated**.

## `target` Attribute — Where it opens

| Value | Behavior | Use for |
|-------|----------|---------|
| `_self` | Same tab (default) | Internal links |
| `_blank` | New tab | External links |
| `_parent` | Parent frame | Breaking out of one iframe level |
| `_top` | Full window | Breaking out of ALL iframes |

```html
<!-- Internal link: same tab -->
<a href="/contact" target="_self">Contact</a>

<!-- External link: new tab + security -->
<a href="https://github.com" target="_blank" rel="noopener noreferrer">GitHub</a>

<!-- Break out of iframe -->
<a href="/full-page" target="_top">View full site</a>
```

## `title` Attribute — Tooltip on hover

```html
<a href="/contact" title="Ir a la pagina de contacto">Contact</a>
<!-- Shows "Ir a la pagina de contacto" when mouse hovers over the link -->
```

**SEO note:** `title` is NOT a ranking factor, but it improves accessibility for screen readers.

## `download` Attribute — Force file download

```html
<!-- Forces download instead of opening in browser -->
<a href="/files/brochure.pdf" download>Download brochure</a>

<!-- With custom filename -->
<a href="/files/brochure.pdf" download="auramip-brochure-2026.pdf">Download</a>
```

**Important from class:** `download` only works with HTTPS. On HTTP it will be ignored by the browser.

## Hash link — Jump to section on same page

```html
<!-- Link to a section on the current page -->
<a href="#Ejercicio">Go to exercise</a>

<!-- The target needs an id attribute -->
<h2 id="Ejercicio">Ejercicio: URL Relativas y Absolutas</h2>
```

This connects to the **URL Hash** class — the `#` is a fragment/anchor that scrolls to an element.

## Deprecated attributes (don't use)

| Value | Was for | Status |
|-------|---------|--------|
| `rel="next"` | Next page in a series | Google ignores it since 2019 |
| `rel="prev"` | Previous page in a series | Google ignores it since 2019 |
| `rel="author"` | Author profile link | No longer used by Google |

## `rel="alternate"` — Still useful

```html
<!-- For multilingual sites (hreflang) -->
<link rel="alternate" hreflang="es" href="https://yoursite.com/es/page">
<link rel="alternate" hreflang="en" href="https://yoursite.com/en/page">

<!-- For RSS feeds -->
<link rel="alternate" type="application/rss+xml" href="/feed.xml">
```

---

## Tipos de Anchor Text

The visible, clickable text of a link. Google uses it to understand what the target page is about.

### 1. Exact-match
```html
<!-- Anchor text = exact target keyword -->
<a href="/registro-marca">registro de marca</a>
```
**SEO:** Most powerful, but overuse looks spammy. Use sparingly.

### 2. Partial-match
```html
<!-- Anchor text contains part of the keyword -->
<a href="/registro-marca">como registrar tu marca en Mexico</a>
```
**SEO:** Natural and safe. Best for most internal links.

### 3. Branded
```html
<!-- Anchor text is the brand name -->
<a href="https://auramip.com">Auramip</a>
```
**SEO:** Natural for homepage and brand mentions.

### 4. Naked URL
```html
<!-- Anchor text IS the URL -->
<a href="https://auramip.com">https://auramip.com</a>
```
**SEO:** Wasted opportunity — tells Google nothing about the content.

### 5. Generic (AVOID)
```html
<!-- Generic, meaningless text -->
<a href="/registro-marca">click aqui</a>
<a href="/registro-marca">leer mas</a>
<a href="/registro-marca">aqui</a>
```
**SEO:** Google can't learn anything from "click here". Always describe the target.

### 6. Image (alt text = anchor text)
```html
<!-- When image is the link, alt = anchor text -->
<a href="/services">
    <img src="/images/services.webp" alt="servicios de registro de marca">
</a>
```
**SEO:** Google reads the `alt` attribute as the anchor text. Never leave `alt` empty on linked images.

## Summary: Class examples explained

```html
<!-- Force download (must be HTTPS) -->
<a href="ejemplo" download>

<!-- Don't pass equity + hide referrer -->
<a href="ejemplo" rel="nofollow noreferrer">

<!-- Don't pass equity + security -->
<a href="ejemplo" rel="nofollow noopener">

<!-- User content + security -->
<a href="ejemplo" rel="ugc noopener">

<!-- Paid link, opens new tab -->
<a href="ejemplo" rel="sponsored" target="_blank">

<!-- Paid link, same tab -->
<a href="ejemplo" rel="sponsored" target="_self">

<!-- Paid link, parent frame -->
<a href="ejemplo" rel="sponsored" target="_parent">

<!-- Tooltip + paid + parent frame -->
<a href="ejemplo" title="soy un hover" rel="sponsored" target="_parent">

<!-- Jump to section on page (hash) -->
<a href="#Ejercicio">
```

## Connection to previous classes

- **URL Hash** — `href="#section"` is a fragment link, handled by the browser
- **URL Buenas Practicas** — Anchor text should use keywords, no prepositions
- **Tipos de Enlace** — `rel` values determine how equity flows
- **Rastreo** — `nofollow` links are still crawled but equity is not passed
