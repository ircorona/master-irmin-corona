# Tipos de Enlace (Link Types)
**Class:** Enlazado Interno — Tipos de Enlace
**References:** [W3Schools Links](https://www.w3schools.com/html/html_links.asp) | [Colors](https://www.w3schools.com/html/html_links_colors.asp) | [Bookmarks](https://www.w3schools.com/html/html_links_bookmarks.asp)

## The `<a>` element

```html
<a href="url" target="_blank" title="tooltip text">visible text</a>
```

| Attribute | Purpose | Values |
|-----------|---------|--------|
| `href` | Where the link goes | URL, `#id`, `mailto:`, `tel:` |
| `target` | Where it opens | `_self`, `_blank`, `_parent`, `_top` |
| `title` | Tooltip on hover | Any text |
| `rel` | Relationship to target | `nofollow`, `noopener`, `noreferrer` |

## Target attribute

```html
<!-- Default: opens in same tab -->
<a href="/contact" target="_self">Contact</a>

<!-- Opens in new tab -->
<a href="https://external-site.com" target="_blank">External</a>

<!-- Opens in parent frame -->
<a href="/page" target="_parent">Parent</a>

<!-- Opens in full window (breaks out of iframes) -->
<a href="/page" target="_top">Full window</a>
```

**SEO rule:** Use `_blank` for external links, `_self` for internal links. Always add `rel="noopener"` with `_blank` for security.

## URL types: Absolute vs Relative

```html
<!-- Absolute: full URL — use for EXTERNAL links -->
<a href="https://www.google.com/search">Google</a>

<!-- Relative: path only — use for INTERNAL links -->
<a href="/contact">Contact</a>
<a href="/folder/file-folder">Projects</a>
```

**SEO best practice:** Always use relative URLs for internal links. If you change domains, all links still work.

## Link types by content

### 1. Text link (most common)
```html
<a href="/registro-marca">registro de marca en México</a>
```

### 2. Image as link
```html
<a href="/services">
    <img src="/images/services-icon.webp" alt="our services">
</a>
```
**SEO note:** Google reads the `alt` attribute as the anchor text when an image is used as a link.

### 3. Email link (`mailto:`)
```html
<a href="mailto:info@auramip.com">Envíanos un correo</a>

<!-- With subject and body pre-filled -->
<a href="mailto:info@auramip.com?subject=Consulta&body=Hola">Contacto</a>
```

### 4. Phone link (`tel:`)
```html
<a href="tel:+525512345678">Llámanos</a>
```

### 5. Bookmark/Anchor link (`#id`) — connects to Hash class
```html
<!-- Step 1: Create the target -->
<h2 id="services">Nuestros Servicios</h2>

<!-- Step 2: Link to it -->
<a href="#services">Ir a servicios</a>

<!-- Step 3: Cross-page bookmark -->
<a href="/about-me#biography">Ver biografía</a>
```

### 6. Button styled as link
```html
<a href="/contact" class="btn">Contáctanos</a>
```
```css
.btn {
    background-color: #f44336;
    color: white;
    padding: 15px 25px;
    text-decoration: none;
    display: inline-block;
}
```

## Link states (CSS pseudo-classes)

```css
/* Unvisited — default: blue underlined */
a:link {
    color: green;
    text-decoration: none;
}

/* Already visited — default: purple underlined */
a:visited {
    color: gray;
}

/* Mouse hovering — default: pointer cursor */
a:hover {
    color: red;
    text-decoration: underline;
}

/* Being clicked — default: red underlined */
a:active {
    color: yellow;
}
```

**Order matters:** Remember **L-V-H-A** (LoVe HAte) — Link, Visited, Hover, Active. CSS must be in this order or it won't work correctly.

## SEO classification of links

### By destination
| Type | Example | SEO impact |
|------|---------|-----------|
| **Internal** | `<a href="/services">` | Passes link equity within your site |
| **External (outbound)** | `<a href="https://other-site.com">` | Passes authority to external site |
| **External (inbound/backlink)** | Another site links to you | Receives authority (most valuable for SEO) |

### By `rel` attribute
```html
<!-- Follow (default) — passes link equity -->
<a href="/page">Normal link</a>

<!-- Nofollow — does NOT pass link equity -->
<a href="https://ad.com" rel="nofollow">Paid link</a>

<!-- Sponsored — tells Google it's a paid link -->
<a href="https://sponsor.com" rel="sponsored">Sponsor</a>

<!-- UGC — user-generated content (comments, forums) -->
<a href="https://user-link.com" rel="ugc">User link</a>

<!-- Security for _blank links -->
<a href="https://external.com" target="_blank" rel="noopener noreferrer">Safe external</a>
```

### By position on page
| Position | Type | SEO weight |
|----------|------|-----------|
| Main navigation (header) | Navigation link | High — appears on every page |
| Body content | Contextual link | Highest — Google values in-content links most |
| Sidebar | Widget link | Medium |
| Footer | Footer link | Low — Google gives footer links less weight |

## Connection to our project

Current internal links in `header.php`:
```html
<a href="/">Home</a>
<a href="/about-me">About Me</a>
<a href="/contact">Contact</a>
<a href="/folder/file-folder">Projects</a>
<a href="https://github.com/ircorona">My GitHub</a>  <!-- external: opens in same tab! -->
<a href="/ejemplo2">Time Demo</a>
```

**Issues to fix:**
- GitHub link should have `target="_blank"` and `rel="noopener noreferrer"` (external link)
- No contextual links in page body content (only navigation links)
- No bookmark/anchor links used yet
