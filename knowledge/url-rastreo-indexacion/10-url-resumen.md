# URL: Resumen (Complete URL Anatomy)

## The 8 parts of a URL

```
https://carlos.sanchezdonate.com:443/articulo/codigos-de-respuesta/?parametro#Bibliografia
  ↑       ↑        ↑         ↑   ↑            ↑                        ↑          ↑
  1       2        3         4   5            6                        7          8
```

| # | Part | Example | Knowledge file |
|---|------|---------|---------------|
| 1 | Protocolo | `https://` | [04-htaccess](04-htaccess-rewrite.md) (HTTPS redirect) |
| 2 | Subdominio | `carlos` | This file |
| 3 | Dominio | `sanchezdonate` | This file |
| 4 | Extensión de dominio | `.com` | This file |
| 5 | Puerto (Port) | `:443` | This file |
| 6 | Ruta (Path) | `/articulo/codigos-de-respuesta/` | [01-ruta](01-url-sintaxis-ruta.md) |
| 7 | Parámetro (Query) | `?parametro=value` | [02-parametros](02-url-sintaxis-parametros.md) |
| 8 | Ancla (Hash/Fragment) | `#Bibliografia` | [03-hash](03-url-sintaxis-hash.md) |

---

## 1. Protocolo (`https://`)

The communication method between browser and server.

| Protocol | Use | Secure? |
|----------|-----|---------|
| `http://` | Standard web traffic | No — data sent in plain text |
| `https://` | Encrypted web traffic | Yes — SSL/TLS encryption |
| `ftp://` | File transfer | No |
| `data:` | Inline data (Data URIs) | N/A — no server involved |

**SEO impact:** Google confirmed HTTPS is a ranking factor. Always redirect HTTP → HTTPS.
See: [04-htaccess-rewrite.md](04-htaccess-rewrite.md)

## 2. Subdominio (`carlos`)

The prefix before the main domain. Each subdomain is treated as a **separate website** by Google.

```
carlos.sanchezdonate.com     ← subdomain "carlos"
www.sanchezdonate.com        ← subdomain "www"
blog.sanchezdonate.com       ← subdomain "blog"
sanchezdonate.com            ← no subdomain (root domain)
```

**SEO impact:**
- Subdomains do NOT inherit SEO authority from the main domain
- `blog.yoursite.com` competes separately from `yoursite.com`
- Prefer subdirectories over subdomains when possible:
  - Bad for SEO: `blog.yoursite.com/post` (separate domain authority)
  - Good for SEO: `yoursite.com/blog/post` (shares domain authority)

**Tool to find subdomains:** https://subdomainfinder.c99.nl/
Use this to discover all subdomains of a competitor's site — reveals staging environments, apps, blogs, and hidden services.

**When to use subdomains:**
- Different language/country versions (`en.yoursite.com`, `mx.yoursite.com`)
- Completely separate applications (`app.yoursite.com`)
- Staging/development (`staging.yoursite.com`)

## 3. Dominio (`sanchezdonate`)

The unique name that identifies your website. Chosen once when you register it.

**SEO best practices for domains:**
- Keep it short and memorable
- Avoid hyphens if possible (1 hyphen OK, 2+ looks spammy)
- Exact Match Domains (EMDs) have less weight now (`registro-marca-mexico.com`)
- Brand names are stronger long-term (`auramip.com`)

## 4. Extensión de dominio (`.com`)

Also called TLD (Top-Level Domain).

| Extension | Use | SEO impact |
|-----------|-----|-----------|
| `.com` | Global, commercial | Strongest — trusted worldwide |
| `.es` | Spain | Geo-targeted — ranks better in Spain |
| `.mx` | Mexico | Geo-targeted — ranks better in Mexico |
| `.org` | Organizations | Neutral |
| `.io`, `.dev` | Tech | Neutral |
| `.com.mx` | Mexico commercial | Geo-targeted + commercial intent |

**For Auramip:** `.com` is correct — targets both US and Mexico markets.

**Punycode:** Domains with special characters must be converted:
- `españa.com` → `xn--espaa-rta.com`
See: [08-url-buenas-practicas.md](08-url-buenas-practicas.md)

## 5. Puerto (Port) — `:443`

The port number tells the server WHICH service to use. It goes right after the domain.

```
domain.com:443    ← HTTPS (port 443)
domain.com:80     ← HTTP (port 80)
domain.com:8080   ← Common for development/testing
localhost:3000    ← Node.js dev server
localhost:8888    ← Laragon default
```

| Port | Protocol | Notes |
|------|----------|-------|
| `80` | HTTP | Default — browser hides it (`domain.com` = `domain.com:80`) |
| `443` | HTTPS | Default — browser hides it (`domain.com` = `domain.com:443`) |
| `3306` | MySQL | Database connections (never exposed to browser) |
| `8080` | HTTP alt | Common for dev servers and proxies |

**Why you never see ports in URLs:**
Browsers hide the default ports. `https://domain.com` is actually `https://domain.com:443` — the browser just doesn't show `:443` because it's the default for HTTPS. You only see a port when it's non-standard (like `localhost:8888`).

**SEO impact:** None directly, but if your site is accessible on a non-standard port (e.g., `:8080`), Google might index it as a separate site. Always serve production on standard ports (80/443).

## 6. Ruta — See [01-url-sintaxis-ruta.md](01-url-sintaxis-ruta.md)

The path to the specific page. Handled by the front controller or served as a static file.

## 7. Parámetro — See [02-url-sintaxis-parametros.md](02-url-sintaxis-parametros.md)

Query string sent to the server. Used for cache busting, tracking (QR), content variation.

## 8. Ancla — See [03-url-sintaxis-hash.md](03-url-sintaxis-hash.md)

Fragment identifier. Browser-only, never sent to server. Scrolls to an element by `id`.

---

## How each part flows

```
Browser builds the request:
  Protocol  → decides HTTP or HTTPS (encryption)
  Domain    → DNS resolves to server IP address
  Path      → server/Apache finds the file or routes via front controller
  Parameter → PHP reads $_GET and modifies response
  Hash      → browser scrolls to #id (never leaves the browser)
```

## Related knowledge files
- [01-url-sintaxis-ruta.md](01-url-sintaxis-ruta.md) — Front controller, virtual pages
- [02-url-sintaxis-parametros.md](02-url-sintaxis-parametros.md) — Cache busting, memory vs disk cache
- [03-url-sintaxis-hash.md](03-url-sintaxis-hash.md) — Anchor navigation
- [04-htaccess-rewrite.md](04-htaccess-rewrite.md) — Rewrite rules, HTTPS redirect
- [05-data-uri-base64.md](05-data-uri-base64.md) — Data URIs (the `data:` protocol)
- [08-url-buenas-practicas.md](08-url-buenas-practicas.md) — URL best practices, Punycode
- [09-url-qr-codes.md](09-url-qr-codes.md) — QR codes with parameter tracking
