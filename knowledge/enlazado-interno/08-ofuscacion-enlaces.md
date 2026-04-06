# Ofuscación de Enlaces (Link Obfuscation)
**Class:** Enlazado Interno — Ofuscación de Enlaces
**Reference:** [Cómo Ofuscar Enlaces - Carlos Sánchez](https://carlos.sanchezdonate.com/articulo/como-ofuscar-enlaces/)

## What is Link Obfuscation?

Making a "link" effect that search engines cannot read as a real link. The link is only readable for users, not for Google.

```
Normal link:     <a href="/page">Text</a>     → Google follows it ✓
Obfuscated link: <span onclick="...">Text</span> → Google can't follow ✗
```

## Why Obfuscate? (Link Juice / PageRank Sculpting)

### The Problem with nofollow

When you add `nofollow`, the link equity is NOT redistributed — it's **wasted**:

```
Page with 4 links (100% equity):
├── Link A (follow)    → receives 25%
├── Link B (follow)    → receives 25%
├── Link C (nofollow)  → receives 25% BUT it's wasted
└── Link D (follow)    → receives 25%

NOT what you might expect:
├── Link A → 33%
├── Link B → 33%
├── Link C → 0% (nofollow)
└── Link D → 33%
```

**nofollow does NOT redistribute equity** — it just wastes that portion. This is why PageRank Sculpting with nofollow no longer works.

### The Solution: Obfuscation

Obfuscated links don't exist as `<a>` tags, so Google doesn't count them at all:

```
Page with 4 elements (100% equity):
├── <a href="/page-a">Link A</a>           → receives 50%
├── <a href="/page-b">Link B</a>           → receives 50%
├── <span onclick="...">Obfuscated C</span> → not a link, not counted
└── <span onclick="...">Obfuscated D</span> → not a link, not counted
```

Now 100% of equity flows to only the real links.

## When to Obfuscate

| Obfuscate | Don't obfuscate |
|-----------|----------------|
| Login/register pages | Service pages |
| Privacy policy, legal pages | Blog articles |
| Pagination | Category pages |
| Duplicate filter pages | Homepage |
| External partner links you don't want to endorse | Internal navigation |
| Language switcher (if same content) | CTAs |

## How to Obfuscate — Methods

### Method 1: onclick (OUTDATED — use with caution)

```html
<span onclick="location.href='https://example.com'" style="cursor:pointer">
    Click here
</span>
```

**Warning from class:** Google's documentation now states they CAN read onclick events. Javier Morell confirmed Google has crawled pages only linked via onclick. **This method is no longer reliable.**

### Method 2: Event Listener (RECOMMENDED)

More complex but harder for Google to detect:

```html
<!-- HTML -->
<span class="obf-link" data-url="aHR0cHM6Ly9leGFtcGxlLmNvbQ==">
    Click here
</span>

<!-- JavaScript (in external file or cached) -->
<script>
document.querySelectorAll('.obf-link').forEach(function(el) {
    el.style.cursor = 'pointer';
    el.addEventListener('click', function() {
        window.location.href = atob(this.dataset.url);
    });
});
</script>
```

**Advantages:**
- JavaScript in external file = cached = better WPO
- Base64 encoding adds extra layer
- Better control over obfuscated links
- No `<a>` tag = Google can't interpret it as a link

### Method 3: Encryption (ADVANCED)

Base64 encode the URL to make it even harder to detect:

```javascript
// Encode: btoa('https://example.com') → 'aHR0cHM6Ly9leGFtcGxlLmNvbQ=='
// Decode: atob('aHR0cHM6Ly9leGFtcGxlLmNvbQ==') → 'https://example.com'
```

**Note from article:** Heavy encryption complicates maintenance and is usually overkill. Base64 is sufficient.

### Method 4: UTM Parameters for Tracking

If you want the target site to know the click came from you:

```html
<span class="obf-link" 
      data-url="aHR0cHM6Ly9wYXJ0bmVyLmNvbT91dG1fc291cmNlPWF1cmFtaXA=">
    Visit Partner
</span>
<!-- Decodes to: https://partner.com?utm_source=auramip -->
```

## Critical Rules

### NEVER use `<a>` tag for obfuscation

```html
<!-- BAD: Google can still try to crawl this even with JavaScript -->
<a href="javascript:void(0)" data-url="/page">Link</a>

<!-- GOOD: No <a> tag at all -->
<span class="obf-link" data-url="encoded-url">Link</span>
```

Per Google's documentation: even with JavaScript, if it's an `<a>` tag, Google may attempt to crawl it.

### This is NOT Cloaking

**Cloaking** = showing DIFFERENT content to users vs search engines (Black Hat SEO, penalized)
**Obfuscation** = showing the SAME content to both, but the "link" functionality uses JavaScript that Google can't interpret as a link

```
Cloaking:      User sees page A, Google sees page B         → PENALIZED
Obfuscation:   User sees clickable span, Google sees span   → SAME content, legal
```

Google's own documentation states that JavaScript functionality that improves user accessibility is NOT considered deceptive.

## rel Attributes Review (from article)

| Attribute | Effect | Use case |
|-----------|--------|----------|
| (none) | Dofollow — passes equity | Normal internal/external links |
| `nofollow` | Indication to not follow | Links you don't want to endorse |
| `sponsored` | Same as nofollow | Paid/advertising links |
| `ugc` | Same as nofollow | User-generated content (comments) |
| `noopener` | Prevents window.opener access | Security for target="_blank" |
| `noreferrer` | noopener + hides referrer | When you don't want target to know source |

**Important:** All rel values are **indications**, not directives. Google may choose to follow nofollow links anyway. This is why obfuscation is more reliable.

## Applying to Auramip.com

Based on Asdrubal Link Analyzer data, pages to consider obfuscating:

| Page | Reason to obfuscate |
|------|-------------------|
| /es/aviso-de-privacidad/ | Legal page, doesn't need equity |
| /privacy-notice/ | Same — legal page |
| Language switcher links | Same content, different language |
| External IMPI/INDAUTOR links | Government sites don't need our equity |

## Bibliography

- [Google: Hidden Text and Links](https://developers.google.com/search/docs/advanced/guidelines/hidden-text-links)
- [Google: Cloaking](https://developers.google.com/search/docs/advanced/guidelines/cloaking)
- [Google: Qualify Outbound Links](https://developers.google.com/search/docs/advanced/guidelines/qualify-outbound-links)
- [Google: Evolving nofollow](https://developers.google.com/search/blog/2019/09/evolving-nofollow-new-ways-to-identify)
- [WooRank: What is Link Juice](https://www.woorank.com/es/edu/seo-guides/que-es-el-link-juice)

## Connection to Previous Classes

- **Estrategias de Enlazado** — Obfuscation is a key strategy for controlling equity flow
- **Errores Comunes** — nofollow doesn't redistribute equity (common misconception)
- **Atributos de Enlaces** — rel attributes are indications, not directives
- **Rastreo** — Obfuscated links save crawl budget
- **URL Parámetros** — UTM parameters can track obfuscated link clicks
