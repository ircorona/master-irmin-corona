# URL: Buenas Prácticas (Best Practices)

## Official reference
https://developers.google.com/search/docs/crawling-indexing/url-structure

## Rules

### 1. Trailing slash consistency
Never have both versions of the same URL:
- `agencia-seo-valencia/` and `agencia-seo-valencia` = duplicate content
- Pick ONE format and 301 redirect the other

### 2. No special characters (ñ, á, é)
Convert to Punycode or remove:
- `españa.com` → `xn--espaa-rta.com`
- `año` → `ano`
- Only use: `a-z`, `0-9`, and hyphens `-`

### 3. Keywords everywhere — even in PDFs
- Bad: `document1.pdf`
- Good: `registro-marca-impi.pdf`

### 4. No spaces
- Bad: `/mi pagina` → becomes `/mi%20pagina`
- Good: `/mi-pagina`

### 5. No capital letters
URLs are case-sensitive on Linux servers:
- Bad: `/Agencia-SEO-Valencia`
- Good: `/agencia-seo-valencia`

### 6. No prepositions
Remove stop words (de, en, para, con, the, of, in, for):
- Bad: `/registro-de-marca-en-mexico`
- Good: `/registro-marca-mexico`

### 7. Avoid meaningless numbers
- Bad: `/servicio-1`
- Good: `/registro-marca`
- OK: `/top-10-marcas` (number IS the keyword)

### 8. Hyphens, not underscores
Google treats hyphens as word separators, underscores as joiners:
- Bad: `registro_marca` → Google reads "registromarca"
- Good: `registro-marca` → Google reads "registro marca"
