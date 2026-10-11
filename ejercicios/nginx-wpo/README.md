# WPO en nginx: compresión y caché

Ejercicio de la clase 15 (NGINX WPO) de Servidores (Avanzado): **realiza alguna configuración en nginx para mejorar la performance**.

## Qué hay

| Ruta | Qué es |
|---|---|
| `conf/clase.conf` | El gzip y la caché de clase, tal cual |
| `conf/clase-tipos-arreglados.conf` | El mismo bloque con CSS y XML añadidos a `gzip_types`, para ver qué hace `gzip_proxied` |
| `conf/brotli-clase.inc` | El bloque de Brotli de clase (este nginx no lo admite: `prueba.py` lo comprueba con `nginx -t`) |
| `conf/ejercicio.conf` | El ejercicio |
| `sitio/` | El `style.css` real del repositorio, un JS, un sitemap, un HTML y una fuente |
| `prueba.py` | Arranca el nginx 1.27.3 de Laragon con configuración propia y lo comprueba todo |

## Cómo se ejecuta

```bash
python prueba.py            # 29 comprobaciones, sale con 0 si todo pasa
python prueba.py --servir   # deja nginx arrancado
```

Genera los `.gz` precomprimidos en `sitio/` (fuera de git) y no toca el nginx ni el Apache de Laragon. Puertos 8041, 8042 y 8043.

## El código de clase, medido

| Prueba | Resultado |
|---|---|
| HTML | gzip ✅ (`text/html` se comprime siempre) |
| `sitemap.xml` | **Sin comprimir**: nginx sirve `.xml` como `text/xml` y la lista dice `application/xml` |
| CSS y JS | **Sin comprimir**: no están en `gzip_types` |
| CSS y XML (añadidos a la lista) pedidos por un proxy (`Via`) | **Sin comprimir**: `gzip_proxied expired` no comprime respuestas con `Expires` futuro, justo las que tienen caché |
| Caché | CSS/JS `8M` = 240 días, woff2 un año, XML 20 minutos ✅. HTML sin nada |
| Bloque de Brotli | `unknown directive "brotli"`: nginx **no arranca** sin el módulo `ngx_brotli`, que no viene de serie |

## El ejercicio

```nginx
gzip on;
gzip_comp_level 5;
gzip_min_length 1000;
gzip_vary on;
gzip_proxied any;
gzip_types text/plain text/css text/javascript application/javascript application/json
           text/xml application/xml application/rss+xml image/svg+xml font/ttf font/otf;
gzip_static on;                 # sirve estilo.css.gz si existe

open_file_cache max=1000 inactive=60s;
open_file_cache_valid 60s;

location ~* \.(css|js|woff2?|jpe?g|png|webp|avif|svg)$ {
    add_header Cache-Control "public, max-age=31536000, immutable";
}
location ~* \.xml$ {
    expires 20m;
}
location / {
    expires epoch;              # Cache-Control: no-cache
    try_files $uri $uri/ =404;
}
```

Medido: HTML, CSS, JS y los dos sitemaps comprimidos, también a través de un proxy; `estilo.css` de 19 964 a 5 203 bytes servido del `.gz` precomprimido a nivel 9; un sitemap sin `.gz` se comprime al vuelo; `robots.txt` y la woff2 sin tocar; CSS con un año e `immutable` en una sola cabecera; HTML con `no-cache` y 304 al revalidar.

## Niveles de gzip

Medido con zlib (el mismo algoritmo que nginx) sobre el `style.css` del repositorio:

| Nivel | Bytes | Ahorro | Tiempo por compresión |
|---|---|---|---|
| 1 | 6 135 | 70 % | ~125 µs |
| 5 | 5 335 | 74 % | ~230 µs |
| 6 | 5 222 | 74 % | ~265 µs |
| 9 | 5 191 | 74 % | ~420 µs |

Del 5 al 9 se ganan 144 bytes y el tiempo casi se duplica, en cada petición. Por eso: nivel medio al vuelo, y el máximo solo en los precomprimidos (`gzip_static`), que se comprimen una vez.
