# URL Sintaxis: Hash (#)

## Concept
The hash fragment navigates within a page. It targets an element by its `id`.

```
https://example.com/page?param=value#section
                         ↑ server sees  ↑ browser only
```

## Key rule: the server NEVER sees the hash
```php
$_SERVER['REQUEST_URI']  // Returns /page?param=value (no #section)
```

## Implementation

### Step 1 — Anchor links
```html
<nav>
    <a href="#biography">Biography</a>
    <a href="#skills">Skills</a>
</nav>
```

### Step 2 — Target sections with id
```html
<section id="biography">...</section>
<section id="skills">...</section>
```

### Step 3 — Smooth scrolling (CSS)
```css
html { scroll-behavior: smooth; }
```

## Hash vs Parameter vs Path

```
/robots          → core.php decides what to show     (Ruta)
?version=50      → same page, different content       (Parámetro)
#biography       → same page, scroll to section       (Hash)
```

## SEO implications
- Google treats `page#a` and `page#b` as the SAME page
- Hash links improve UX (indirect SEO benefit)
- The old `#!` hashbang pattern is deprecated — use proper routing
