# .htaccess — Apache Rewrite Rules

## Current rules in this project (execution order)

### 1. Remove trailing slashes
```apache
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule (.*)/$ https://%{HTTP_HOST}/$1 [R=301,L,QSA]
```
`/about-me/` → `/about-me`

### 2. Force HTTPS
```apache
RewriteCond %{HTTPS} off
RewriteRule (.*) https://%{HTTP_HOST}/$1 [R=301,L,QSA]
```
`http://` → `https://`

### 3. Clean URLs (hide .php extension)
```apache
RewriteCond %{REQUEST_URI} !^/enviar_correo\.php$ [NC]
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME}\.php -f
RewriteRule ^([^\.]+)$ $1.php [L]
```
`/about-me` → internally serves `about-me.php`

### 4. Redirect .php URLs to clean URLs
```apache
RewriteCond %{ENV:REDIRECT_STATUS} ^$
RewriteCond %{REQUEST_URI} \.php$
RewriteCond %{REQUEST_URI} !/enviar_correo\.php$ [NC]
RewriteRule ^(.*)\.php$ /$1 [R=301,L,NE]
```
If someone visits `/about-me.php` → redirects to `/about-me`

### 5. Front controller (fallback)
```apache
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ assets/core.php [L]
```
Unknown URLs → `core.php` handles them

## Flag reference
| Flag | Meaning |
|------|---------|
| `[L]` | Last rule — stop processing |
| `[R=301]` | Permanent redirect |
| `[R=404]` | Return 404 |
| `[QSA]` | Preserve query string parameters |
| `[NC]` | Case insensitive (needed on Windows Apache) |
| `[NE]` | No escape — don't encode special characters |

## Reviewer feedback (Carlos)
- Use hyphens `-` in URLs, not underscores `_`
- Use relative URLs with leading `/` not `./`
- The `enviar_correo.php` exception is needed for form POST handling
