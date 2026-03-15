# URL Sintaxis: Ruta (Path)

## Concept
"No es que creo el archivo A y se ve el archivo A. Yo puedo programar que existan muchos archivos que en realidad no existen — yo los hago existir." — Clase

This is the **front controller pattern**: one file handles all routing.

## How it works

```
Level 1 (static):     /about-me  →  about-me.php        (1 file = 1 page)
Level 2 (router):     /robots    →  core.php + if/else   (1 file = many pages)
Level 3 (database):   /post/123  →  core.php + DB query  (1 file = unlimited pages)
```

## Implementation

### .htaccess rule (sends unknown URLs to core.php)
```apache
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ assets/core.php [L]
```

### core.php (the router)
```php
<?php
$request_uri = $_SERVER['REQUEST_URI'];

if ($request_uri == '/robots') {
    echo "Pagina bloqueada por el robots.txt";
} elseif ($request_uri == '/error4xx') {
    header("HTTP/1.0 418 I'm a teapot");
    echo 'Soy una tetera';
} elseif ($request_uri == '/existir') {
    echo 'Esta pagina si existe';
} else {
    header("HTTP/1.0 404 Not Found");
    include 'errores/404.php';
}
```

## Key takeaway
- Real files are served normally (existing .php files still work)
- The front controller only catches URLs that don't match any real file
- This is how WordPress, Laravel, and every modern framework works
- "La base de datos son las variables" — the database replaces the hardcoded if/else blocks

## Applied in
- `assets/core.php` — this project
- Auramip WordPress — uses `index.php` as front controller with WP routing
