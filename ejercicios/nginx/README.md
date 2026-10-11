# nginx: el `server` de clase y las clases anteriores sin `.htaccess`

Ejercicio de la clase 13 (NGINX) de Servidores (Avanzado). nginx no lee `.htaccess`: todo va en un bloque `server` de su configuración y se aplica al recargar. La clase enseña el `server` que genera Laragon para un dominio; el ejercicio lo corrige y traduce a nginx lo de las clases 06 a 12.

## Qué hay

| Ruta | Qué es |
|---|---|
| `conf/clase.conf` | El `server` de la foto de clase, tal cual (solo cambian puertos y `root`) |
| `conf/ejercicio.conf` | El ejercicio: el `server` corregido + redirección https, X-Robots-Tag, bloqueo por user-agent, gzip y caché, indirección, hotlinking y descargas |
| `conf/demo-add-header.conf` | La trampa de herencia de `add_header` |
| `sitio/` | Una web de prueba con un `.htaccess` (que nginx ignora), un `.env`, una carpeta sin índice, un PDF, una imagen… |
| `prueba.py` | Arranca el nginx 1.27.3 y el `php-cgi.exe` de Laragon con configuración propia y lo comprueba todo |

## Cómo se ejecuta

```bash
python prueba.py            # 34 comprobaciones, sale con 0 si todo pasa
python prueba.py --servir   # deja nginx arrancado
```

No toca el nginx ni el Apache de Laragon: puertos 8061/8461 (clase), 8062/8462 (ejercicio), 8063 (demo) y 9161 (PHP). Antes de arrancar ejecuta `nginx -t`, que valida la sintaxis.

## El `server` de clase, medido

| Prueba | Resultado |
|---|---|
| `/viejo` con un `Redirect` en el `.htaccess` | 200: **nginx no lee el `.htaccess`** |
| `/no-existe/` | **200 con la portada**: `try_files ... /index.php` manda todo lo que no existe al `index.php` (soft 404 si el PHP no responde 404) |
| `/sub/` (carpeta sin índice) | **200 con el listado de ficheros**: `autoindex on` |
| `/.htaccess` | 403: lo cubre `location ~ /\.ht` |
| `/.env` | **200 con la contraseña dentro**: la regla solo cubre `.ht*` |
| `http://` y `https://` | Las dos 200: web duplicada, sin redirección |
| TLS 1.0 | Rechazado aunque esté en `ssl_protocols`: el OpenSSL 3 de nginx ya no lo admite con esos cifrados |
| Protocolo | HTTP/1.1: falta `http2 on` |

Es una configuración para desarrollo local (Laragon la genera así). Copiada a producción, lista carpetas, expone `.env` y `.git`, y duplica la web en http y https.

## El ejercicio

| Clase | En `.htaccess` | En nginx | Medido |
|---|---|---|---|
| 06 | `RewriteRule` a https | `server` aparte con `return 301 https://$host$request_uri;` | 301 con la query intacta |
| 07 | `Header set X-Robots-Tag` | `add_header X-Robots-Tag "noindex" always;` en `location ~* \.pdf$` | ✅ |
| 08 | `RewriteCond %{HTTP_USER_AGENT}` + `[F]` | `if ($http_user_agent ~* "...") { return 403; }` | 403 |
| 09 | `mod_deflate`/`mod_brotli` + `mod_expires` | `gzip on; gzip_types ...;` + `expires 1y;` | gzip y `max-age=31536000` (nginx no trae Brotli de serie) |
| 10 | `RewriteRule [END]` + 404 con `THE_REQUEST` | `rewrite ... last;` + `location ^~ /ejemplo/ { internal; }` | Indirección 200, `/ejemplo/` 404, sin barra 301 a la pública |
| 11 | `RewriteCond %{HTTP_REFERER}` | `if ($http_referer ...) { return 204; }` + `valid_referers none blocked server_names ...;` | Google 204, otra web 403, sin `Referer` 200 |
| 12 | `Header set Content-Disposition` | `add_header Content-Disposition "attachment";` | `.txt` se descarga, `robots.txt` no |

Además: `try_files $uri $uri/ =404` (404 de verdad, sin listado), ficheros ocultos a 404 salvo `/.well-known/`, `http2 on` y los valores por defecto de TLS de nginx (TLS 1.2 y 1.3).

## Dos trampas de nginx

1. **`add_header` no se suma, se sustituye.** Una cabecera puesta en el `server` desaparece en cualquier `location` que tenga su propio `add_header` (medido en `demo-add-header.conf`). Por eso el ejercicio repite `X-Content-Type-Options` en cada `location` con cabeceras.
2. **Las `location` con regex se evalúan en orden y gana la primera.** La de `robots.txt` tiene que ir antes que la de `\.txt$`, y la de la indirección después de la de ficheros ocultos: si no, `/indireccion/asdrubal/.env` se serviría desde `/ejemplo/.env`.
