# Generar códigos de respuesta desde `.htaccess`

Ejercicio de **Servidores (Avanzado), clase 06 Generar códigos de respuesta**.

## El enunciado

1. Redirecciona un directorio a otro, haciendo que todas las páginas que cuelguen también se redireccionen a la otra versión.
2. Crea un código de respuesta 503 en una página específica.
3. Redirecciona con un 302 todo un directorio y todas las páginas que cuelgan a una página específica.

## La solución

Las tres reglas están en [`sitio/.htaccess`](sitio/.htaccess), con `RedirectMatch`:

```apache
# 1. /blog/* -> /noticias/* (301, un solo salto incluso para /blog sin barra)
RedirectMatch 301 ^/blog(?:/(.*))?$ /noticias/$1

# 2. 503 en una sola página, con Retry-After y página de error propia
RedirectMatch 503 ^/mantenimiento/?$
Header always set Retry-After "3600" "expr=%{REQUEST_STATUS} == 503"
ErrorDocument 503 /errores/503.html

# 3. /promociones/* -> una sola página (302, sin $1 en el destino)
RedirectMatch 302 ^/promociones(/.*)?$ /oferta-san-valentin/
```

Tres detalles que separan una regla que funciona de una que funciona bien:

| Detalle | Por qué |
|---|---|
| `/blog` va a `/noticias/` **con barra** | Si fuera a `/noticias`, `mod_dir` añadiría la barra con otro 301: cadena de dos saltos |
| `(?:/(.*))?` y no `/?(.*)` | Con `/?(.*)`, `/blogger/` también casaría y acabaría en `/noticias/ger/` |
| El 503 lleva `Retry-After` | Es lo que le dice a Google cuándo volver. `always` porque es una respuesta de error |

## Las demos de clase

[`demos/`](demos/) reproduce los ejemplos de la clase para ver qué hacen de verdad:

| Demo | Resultado medido |
|---|---|
| El bucle de la foto (`/sobre-mi` ↔ `/carpeta/archivo-carpeta`) | 10 saltos sin llegar nunca a un 200: `ERR_TOO_MANY_REDIRECTS` |
| QR de las servilletas tal cual (`qr=servilletas` sin anclar) | También redirige `?xqr=servilletas2` |
| QR anclado (`(^\|&)qr=servilletas(&\|$)`) | Solo el parámetro exacto, esté donde esté en la query string |
| `<Files ¨.ht¨> … </Files ¨.ht¨>` de la pizarra | **500**: `</Files> directive missing closing '>'` |
| `<Files ".ht">` | Sintaxis válida, pero **`.htaccess` y `.htpasswd` se sirven con 200** |
| `<Files ".ht*">` | 403 para los dos. Es la versión del `httpd.conf` de Apache |

`demos/.htaccess` incluye un bucle a propósito: no copiar a producción.

## Cómo probarlo

```bash
python prueba.py
```

Levanta un Apache de usar y tirar con el binario de Laragon (`C:/laragon/bin/apache/httpd-*`), con config propia en una carpeta temporal y en los puertos 8099 (sitio) y 8098 (demos). No toca el Apache de Laragon ni necesita que esté arrancado. Hace las peticiones sin seguir redirecciones, comprueba código, `Location`, `Retry-After`, el bucle y que ninguna redirección del ejercicio tenga más de un salto, y para el servidor.

Resultado: **29/29 comprobaciones OK, exit 0**.

Un hallazgo al montarlo: sin `<Directory "/"> AllowOverride None`, Apache subía hasta el `.htaccess` de la raíz del repo y daba 500 en todo. Es exactamente lo que dice la documentación: Apache busca `.htaccess` en **cada directorio padre** en cada petición.
