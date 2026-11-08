# Página de mantenimiento con 503

Ejercicio de la clase **Migraciones 20 · Copiar web de otro proyecto** (Carlos). En clase se usa un `.htaccess` que da 503 a todo el mundo salvo a la IP del equipo, con la página de mantenimiento escrita dentro del propio `.htaccess`. Aquí se prueba tal cual en un Apache real y se compara con una versión mejorada.

| Fichero | Qué es |
|---|---|
| `variantes/clase.htaccess` | El de clase, con una IP y unos datos de contacto de ejemplo |
| `variantes/mejorada.htaccess` | `robots.txt` fuera del 503, `Retry-After`, IPv6 del equipo, sin caché, página aparte |
| `variantes/mantenimiento.html` | La página de la versión mejorada |
| `prueba.py` | Monta un Apache temporal y prueba las dos variantes |

`prueba.py` usa el `httpd.exe` de Laragon con su propio `httpd.conf` en una carpeta temporal; no toca la configuración de Laragon. Escucha en `127.0.0.1` y en `[::1]` para simular al equipo saliendo por IPv6.

## Ejecutar

```bash
python prueba.py
python prueba.py --httpd "C:/ruta/httpd.exe" --puerto 8136
```

Sale con 0 si los 6 casos dan lo esperado.

## Resultado (Apache 2.4.62, Python 3.11.9, 2026-11-08)

```
Variante  Quién        /     robots  Retry-After  Content-Type                    Resultado
clase     visitante    503   503     no           text/html; charset=iso-8859-1   correcto
clase     equipo IPv4  200   200     no           text/html                       correcto
clase     equipo IPv6  503   503     no           text/html; charset=iso-8859-1   correcto
mejorada  visitante    503   200     3600         text/html                       correcto
mejorada  equipo IPv4  200   200     no           text/html                       correcto
mejorada  equipo IPv6  200   200     no           text/html                       correcto

Casos correctos: 6 de 6
```

Lo que se ve:

1. **El de clase funciona**: el visitante recibe 503 con la página y la IP permitida ve la web.
2. **El mensaje en línea sale con `charset=iso-8859-1`**: por eso en clase las tildes van como `&aacute`. Una «á» escrita tal cual saldría «Ã¡».
3. **Da 503 también en `robots.txt`**: Google para de rastrear el sitio entero.
4. **No envía `Retry-After`.**
5. **Si el equipo navega por IPv6, también recibe el 503.**

## Cómo usarlo

1. Copiar `mejorada.htaccess` arriba del todo en el `.htaccess` de la web y subir `mantenimiento.html` a la raíz.
2. Cambiar `203\.0\.113\.5` por tu IPv4 y `2001:db8::5` por tu IPv6 (o borrar esa línea si no tienes).
3. Comprobar desde otra conexión: `curl -I https://dominio.tld/` debe dar 503 con `Retry-After`, y `curl -I https://dominio.tld/robots.txt` 200.
4. Detrás de Cloudflare u otro CDN, `REMOTE_ADDR` es la IP del CDN: esta regla no sirve tal cual.
