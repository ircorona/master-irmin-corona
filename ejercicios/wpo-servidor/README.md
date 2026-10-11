# WPO desde el servidor: caché, compresión, HTTP/2 y Early Hints

Ejercicios de la clase 09 (WPO) de Servidores (Avanzado):

1. Configura la caché de usuario de tu página.
2. Encuentra una web que tenga Brotli implementado y una que no.
3. Añade Brotli a tu web.
4. Extra: pon tu código en el `httpd.conf` (caché de servidor, HTTP/2, Early Hints).

## Qué hay

| Ruta | Qué es |
|---|---|
| `sitio/.htaccess` | Ejercicios 1 y 3, más WebP en la misma URL y Early Hints |
| `sitio/dinamico/` | Un PHP "lento" (300 ms) y su `.htaccess` con `s-maxage` para la caché de servidor |
| `prueba.py` | Arranca un Apache propio con el `httpd.conf` del ejercicio 4 y lo comprueba todo |
| `demos/` | El código de clase **tal cual**, para medir qué hace de verdad |

## Cómo se ejecuta

```bash
pip install h2          # solo para la parte de HTTP/2
python prueba.py        # 38 comprobaciones, sale con 0 si todo pasa
python prueba.py --servir   # deja Apache arrancado para mirarlo con curl
```

Usa el Apache 2.4.62 y el `php-cgi.exe` de Laragon con una configuración propia en una carpeta temporal. No toca el Apache de Laragon: puertos 8096 (sitio), 8097 (demos), 8443 (HTTPS + HTTP/2) y 9196 (PHP).

## Ejercicio 1: caché de usuario

Un solo mecanismo (`mod_expires`), para no tener dos fuentes de `Cache-Control` que se contradigan:

| Tipo | Tiempo | Por qué |
|---|---|---|
| HTML | `max-age=0` | El navegador revalida cada vez (con `ETag`/`Last-Modified` es un 304 barato) |
| CSS, JS, imágenes, fuentes, favicon | 1 año | Se publica uno nuevo cambiando la URL: `estilo.css?v=2` |
| `sitemap.xml`, `robots.txt` | 1 día | Cambian, pero no a diario |
| El resto | 1 hora | Por defecto prudente |

Los tipos MIME son los que **sirve** Apache (`conf/mime.types`): `image/jpeg`, `image/x-icon`, `font/woff2`, `text/javascript`. Si no coinciden exactamente, la línea no hace nada.

## Ejercicio 2: Brotli sí y Brotli no

Medido el 2026-10-10 con `curl -H "Accept-Encoding: gzip, deflate, br"` y un user-agent de Chrome:

| Web | `Content-Encoding` | Servidor |
|---|---|---|
| sanchezdonate.net | `br` | nginx |
| climbthesearches.com | `br` | hcdn (Hostinger) |
| auramip.com | `br` (y `zstd` si se ofrece) | Cloudflare |
| rae.es | `br` | Cloudflare |
| httpd.apache.org | `gzip` | Apache: la web del proyecto que hace `mod_brotli` no lo usa |
| boe.es | **ninguno** | HTML sin comprimir |

Google responde `br` a un user-agent de Chrome completo y `gzip` a uno recortado: la compresión puede depender del navegador.

## Ejercicio 3: Brotli

```apache
<IfModule mod_filter.c>
<IfModule mod_brotli.c>
<IfModule mod_deflate.c>
AddOutputFilterByType BROTLI_COMPRESS;DEFLATE text/html text/plain text/css text/javascript application/javascript application/json application/xml application/rss+xml image/svg+xml image/x-icon font/ttf font/otf
</IfModule>
</IfModule>
</IfModule>
```

Brotli y gzip encadenados en una sola directiva, para que el orden sea fijo: medido, si el bloque de Deflate va **antes** que el de Brotli, Apache sirve gzip aunque el navegador acepte `br`. Solo texto: JPEG, WebP y WOFF2 ya vienen comprimidos. El CSS de prueba pasa de 14 356 bytes a 2 191 con Brotli y 2 222 con gzip.

## Ejercicio 4 (extra): el `httpd.conf`

Está en la constante `CONF` de `prueba.py`. Lo importante:

```apache
CacheRoot "/ruta/con/permiso/de/escritura/cache"   # de mod_cache_disk
CacheHeader on                                       # X-Cache: HIT/MISS para comprobarlo

<VirtualHost *:80>
    CacheEnable disk /dinamico/        # solo lo dinámico
</VirtualHost>

<VirtualHost *:443>
    Protocols h2 http/1.1
    H2EarlyHints on
    H2Push off
</VirtualHost>
```

| Medido | Resultado |
|---|---|
| PHP de 300 ms, primera y segunda petición | MISS 327 ms → HIT 1 ms, misma página |
| HTTPS | ALPN negocia `h2` |
| Página y PHP por HTTP/2 | `103` con los `Link: preload` y luego `200` |
| CSS por HTTP/2 | Solo `200`: el `<If>` limita el 103 a las páginas |

## Lo que enseñan las demos (código de clase)

| Demo | Medido |
|---|---|
| `expires-clase` | HTML: `Cache-Control` 4 horas pero `Expires` 4 meses. woff2: 4 meses (no el año: `application/font-woff2` no es el tipo). favicon: `image/ico` no es el tipo |
| `brotli-clase` | `AddType x-font/woff .woff` cambia el tipo de las `.woff`, y entonces ninguna regla con `font/woff` las encuentra |
| `deflate-y-brotli` | Con los bloques al revés, gzip gana |
| `dinamico` (bloque `mod_cache` de clase) | El PHP **nunca** se cachea: no manda `Last-Modified`. Con `CacheIgnoreNoLastMod On` sí (en `.htaccess` da 500: solo vale en el `httpd.conf`) |
| `privado` | `CacheEnable disk /` + el `Require ip` de la clase 08: una IP bloqueada recibe 403 hasta que alguien autorizado visita la página; después, **200 desde la caché** |
| `webp-clase` | El navegador acepta WebP y sale el JPEG: al código le falta la regla que reescribe |
| sin `mod_cache_disk` | Apache **no arranca**: `CacheRoot` es de `mod_cache_disk`, y el `<IfModule mod_cache.c>` no lo protege |

## En Laragon

El `httpd.conf` de Laragon trae **comentados** `mod_brotli`, `mod_deflate`, `mod_expires`, `mod_filter`, `mod_http2` y `mod_cache_disk`. Todo lo que esté dentro de `<IfModule>` de esos módulos no hace nada en local, sin avisar. Para probarlo en `master-irmin-corona.test` hay que quitar el `#` de esas líneas y reiniciar Apache.
