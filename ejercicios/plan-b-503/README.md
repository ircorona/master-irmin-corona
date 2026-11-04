# Plan B del lanzamiento: 503 preparado

Ejercicio de la clase **Migraciones 07 · Migración**. La diapositiva «Antes de empezar» pide tener preparados un 503 para cualquier URL y un 404 para `robots.txt`. Aquí están las reglas de Apache listas para usar, probadas en un servidor real, con una IP permitida para seguir validando.

| Fichero | Código en páginas | Código en `robots.txt` | Efecto en Google |
|---|---|---|---|
| `variantes/503-todo.htaccess` | 503 | 503 | Deja de rastrear el sitio (hasta 12 horas) |
| `variantes/503-sin-robots.htaccess` | 503 | 200 | Baja la velocidad de rastreo; lo que Google recomienda al desactivar una web |
| `variantes/diapositiva-404-robots.htaccess` | 503 | 404 | Igual que la anterior, pero **sin restricciones de rastreo**: el 404 no frena nada |

`prueba.py` monta una carpeta temporal con su propio `httpd.conf` y usa el binario de Apache de Laragon sin tocar su configuración ni activar nada en ella.

## Ejecutar

```bash
python prueba.py
python prueba.py --httpd "C:/ruta/httpd.exe" --puerto 8135
```

Sale con 0 si todos los casos dan el código esperado.

## Resultado (Apache 2.4.62, Python 3.11.9, 2026-11-04)

```
Variante                  IP          /       robots.txt  Retry-After  Resultado
503-todo                  visitante   503     503         sí           correcto
503-todo                  permitida   200     200         no           correcto
503-sin-robots            visitante   503     200         sí           correcto
503-sin-robots            permitida   200     200         no           correcto
diapositiva-404-robots    visitante   503     404         sí           correcto
diapositiva-404-robots    permitida   200     200         no           correcto

Casos correctos: 6 de 6
```

## Cómo usarlo

1. Copiar la variante en el `.htaccess` de la web nueva, **arriba del todo**.
2. Cambiar `203\.0\.113\.5` por tu IP, con los puntos escapados.
3. Comprobar: `curl -I https://www.dominio.tld/` debe dar 503 y `Retry-After` desde otra conexión.
4. Quitarla al terminar. Google avisa de que más de 1-2 días puede afectar a cómo aparece la web, y las URLs con 5xx persistente salen del índice.

## Qué enseña

- **El 404 de la diapositiva no es un freno.** La prueba solo mide los códigos que sirve Apache; lo que hace Google con ellos viene de su documentación, no de este ejercicio.
- **`Retry-After` solo sale con el 503**, gracias a la condición `expr`. Con la IP permitida la respuesta queda limpia.
- **La IP permitida** deja validar con la web real mientras Google recibe el 503.

## Límites

- Detrás de un CDN, `REMOTE_ADDR` es la IP del CDN, no la tuya: habría que usar otra condición (en Cloudflare, `CF-Connecting-IP`) o configurar el 503 en el propio CDN. No probado.
- Probado en Apache, no en LiteSpeed ni nginx. La página de error es una cadena de texto, para no depender de un fichero servido por la misma regla.
- `robots.txt` se cachea hasta 24 horas: su cambio no es instantáneo, aunque el servidor ya responda otra cosa.
