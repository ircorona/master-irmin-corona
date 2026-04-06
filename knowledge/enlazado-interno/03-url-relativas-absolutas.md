# URL Relativas y Absolutas
**Class:** Enlazado Interno — URL Relativas y Absolutas

## Absolute vs Relative URLs

### Absolute URL (full path)
```html
<a href="https://auramip.com/services/registro-marca">Registro</a>
```
- Includes protocol + domain + path
- Works from anywhere
- Use for: external links, canonical tags, Open Graph, sitemaps

### Relative URL (path only)
```html
<a href="/services/registro-marca">Registro</a>
```
- No protocol or domain
- Depends on current location
- Use for: internal links (with leading `/`)

## 4 Types of Relative URLs

| Type | Example | What it means |
|------|---------|--------------|
| `<a href="pagina">` | Same folder as current page | Looks in current directory |
| `<a href="categoria/pagina">` | Subfolder of current location | Goes DOWN into a folder |
| `<a href="/categoria/pagina">` | From ROOT | Always starts from site root |
| `<a href="../categoria/pagina">` | One level UP | Goes UP one folder, then into categoria |

### Visual example

```
ROOT/
├── index.php              ← you are HERE
├── about-me.php
├── folder/
│   ├── file-folder.php    ← you are HERE (second example)
│   └── other.php
└── images/
    └── logo.png
```

**From `index.php` (root):**
```html
<a href="about-me">          → ROOT/about-me.php ✓
<a href="folder/file-folder"> → ROOT/folder/file-folder.php ✓
<a href="/about-me">          → ROOT/about-me.php ✓
<a href="../about-me">        → OUTSIDE ROOT ✗ ERROR
```

**From `folder/file-folder.php`:**
```html
<a href="other">              → ROOT/folder/other.php ✓
<a href="folder/file-folder"> → ROOT/folder/folder/file-folder.php ✗ WRONG!
<a href="/about-me">          → ROOT/about-me.php ✓ (always correct)
<a href="../about-me">        → ROOT/about-me.php ✓ (goes up one level)
```

## IMPORTANT: Why `href="pagina"` is DANGEROUS

```html
<!-- WITHOUT leading slash — RELATIVE to current folder -->
<a href="ejemplo">

<!-- This link changes meaning depending on WHERE you are: -->
<!-- From /index.php        → /ejemplo        ✓ -->
<!-- From /folder/page.php  → /folder/ejemplo  ✗ 404! -->
<!-- From /a/b/c/page.php   → /a/b/c/ejemplo   ✗ 404! -->
```

```html
<!-- WITH leading slash — ALWAYS from ROOT -->
<a href="/ejemplo">

<!-- This link ALWAYS means the same thing: -->
<!-- From /index.php        → /ejemplo ✓ -->
<!-- From /folder/page.php  → /ejemplo ✓ -->
<!-- From /a/b/c/page.php   → /ejemplo ✓ -->
```

**Rule: ALWAYS use leading slash `/` for internal links.**

## Infinite crawl loop problem (IMPORTANTE!)

Relative URLs without `/` can create infinite crawl loops:

```
Googlebot visits:  /folder/page
Finds link:        <a href="page">   (no leading slash)
Resolves to:       /folder/page
Finds link:        <a href="page">
Resolves to:       /folder/page      ← infinite loop!

Or worse:
/a/page → /a/a/page → /a/a/a/page → /a/a/a/a/page...
```

Google wastes entire crawl budget on fake URLs that all return 404. This is a real SEO disaster.

## CORS error with PHP images

Using `$_SERVER['DOCUMENT_ROOT']` in image paths can cause CORS errors:

```php
<!-- BAD: generates file system path, can cause CORS -->
echo '<img src="' . $_SERVER['DOCUMENT_ROOT'] . '/images/faq-icon.png" alt="FAQ icon">';
<!-- Output: <img src="C:/laragon/www/mysite/images/faq-icon.png"> ← file path, not URL! -->

<!-- GOOD: relative URL from root -->
echo '<img src="/images/faq-icon.png" alt="FAQ icon">';
<!-- Output: <img src="/images/faq-icon.png"> ← correct URL -->
```

**Solution:** Call the host through PHP instead of using filesystem paths.

## Class Hack: `<base>` tag (IMPORTANTE!)

```html
<head>
    <base href="https://batallaseo.test">
</head>
```

This sets the **base URL for ALL relative links** on the page:

```html
<base href="https://batallaseo.test">

<!-- Now ALL relative links resolve from this base: -->
<a href="/contact">       → https://batallaseo.test/contact
<a href="about">          → https://batallaseo.test/about
<img src="/images/logo.png"> → https://batallaseo.test/images/logo.png
```

**Use cases:**
- Development: set base to local domain (`batallaseo.test`)
- Production: set base to live domain (`batallaseo.es`)
- Migrating domains: change ONE line instead of every link

**Warning:** `<base>` affects ALL relative URLs on the page — links, images, CSS, JS. Use with caution.

## Summary: Which to use?

| Situation | Use | Example |
|-----------|-----|---------|
| Internal link | Relative with `/` | `<a href="/contact">` |
| External link | Absolute | `<a href="https://google.com">` |
| Canonical tag | Absolute | `<link rel="canonical" href="https://yoursite.com/page">` |
| Open Graph | Absolute | `<meta property="og:url" content="https://yoursite.com/page">` |
| Sitemap | Absolute | `<url><loc>https://yoursite.com/page</loc></url>` |
| Images in HTML | Relative with `/` | `<img src="/images/logo.png">` |
| CSS background | Relative with `/` | `background: url('/images/bg.webp')` |

## Exercise: 5 links in our project

```html
<!-- 1. Absolute URL (external) -->
<a href="https://github.com/ircorona" target="_blank" rel="noopener noreferrer">My GitHub</a>

<!-- 2. Relative: same folder -->
<a href="about-me">About Me</a>  <!-- works from index, but DANGEROUS from subfolders -->

<!-- 3. Relative: subfolder -->
<a href="folder/file-folder">Projects</a>  <!-- same risk as above -->

<!-- 4. Relative: from ROOT (BEST) -->
<a href="/contact">Contact</a>  <!-- always correct -->

<!-- 5. Relative: one level up -->
<a href="../about-me">About Me</a>  <!-- from /folder/file-folder.php goes up to root -->
```

**Conclusion:** Always use type 4 (`/path`) for internal links. The others are shown for understanding, not for production use.
