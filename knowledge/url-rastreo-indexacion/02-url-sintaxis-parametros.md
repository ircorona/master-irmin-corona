# URL Sintaxis: Parámetros

## Concept
URL parameters (`?key=value`) tell the server to modify the response. They are sent to the server (unlike hashes).

## Cache busting with parameters

### The problem
`.htaccess` caches CSS for 2 months:
```apache
ExpiresByType text/css "access plus 2 months"
```
If you update CSS, users won't see changes for 2 months.

### The solution — variables.php
```php
<?php
$var_param_monthly = '?' . date('Ym');        // ?202603
define("var_param_monthly", $var_param_monthly);
define("css_version", "v1");
```

### Usage in header.php
```php
<link href="style.css<?php echo constant("var_param_monthly") . constant("css_version"); ?>">
<!-- Outputs: style.css?202603v1 -->
```

### How it works
| Month | URL | Browser behavior |
|-------|-----|-----------------|
| March | style.css?202603v1 | Downloads fresh |
| April | style.css?202604v1 | Downloads fresh (new URL) |
| April again | style.css?202604v1 | Serves from cache |

The browser treats `?202603` and `?202604` as different files.

## Memory cache vs Disk cache

| | Memory Cache | Disk Cache |
|---|---|---|
| Where | RAM | Hard drive |
| Speed | ~5ms | ~50ms |
| Survives closing tab? | No | Yes |
| Used for | Small files needed NOW (JS, CSS) | Larger files for later visits (images, fonts) |

### How to see it
Chrome → F12 → Network tab → reload page → check "Size" column:
- `(memory cache)` = from RAM
- `(disk cache)` = from hard drive
- `142 KB` = downloaded from server

### Server controls cache via headers
```
Cache-Control: max-age=3600    → cache for 1 hour
Cache-Control: no-cache        → check server first
Cache-Control: no-store        → never cache
```

## Applied in
- `assets/variables.php` — monthly cache busting in this project
- Auramip WordPress — uses `versiones.php` with same pattern for theme assets
