# Bloqueo de indexación: el entorno de pruebas cerrado, la web lanzada abierta

Ejercicio de la clase **Migraciones 05 · Timings**. En la fase de maquetación/desarrollo el SEO entrega las «especificaciones para evitar indexación» del entorno de pruebas, y el día del lanzamiento hay que comprobar que ese bloqueo **no** ha llegado a producción. Este script hace las dos comprobaciones.

| Fichero | Qué hace |
|---|---|
| `indexable.py` | Pide la URL como Googlebot y mira código HTTP (401/403), `X-Robots-Tag`, `<meta name="robots">` / `googlebot` y `robots.txt`. Con `--esperado staging` falla si la URL es indexable; con `--esperado produccion` falla si tiene cualquier bloqueo |

Solo librería estándar de Python.

## Ejecutar

```bash
python indexable.py --prueba                                       # 6 casos en 127.0.0.1:8133
python indexable.py --esperado staging https://beta.ejemplo.com/   # antes del lanzamiento
python indexable.py --esperado produccion https://auramip.com/     # el día del lanzamiento
```

Sale con código 0 si todas las URLs están como se esperaba y 1 si alguna no.

## Resultado (Python 3.11, 2026-11-02)

```
== http://127.0.0.1:8133/prod/  (HTTP 200)
   sin bloqueos
   -> OK: indexable: bien para producción
== http://127.0.0.1:8133/staging-cabecera/  (HTTP 200)
   X-Robots-Tag: noindex, nofollow
   -> OK: bloqueado: bien para pruebas
== http://127.0.0.1:8133/staging-meta/  (HTTP 200)
   meta: <meta name="robots" content="noindex">
   -> OK: bloqueado: bien para pruebas
== http://127.0.0.1:8133/staging-clave/  (HTTP 401)
   HTTP 401: protegido
   -> OK: bloqueado: bien para pruebas
== http://127.0.0.1:8133/privado/  (HTTP 200)
   meta: <meta name="robots" content="noindex">
   robots.txt: Disallow para Googlebot (bloquea rastreo, no indexación)
   -> OK: bloqueado: bien para pruebas (pero el Disallow impide que Google vea el noindex: mejor quitarlo o usar contraseña)
== http://127.0.0.1:8133/olvidado/  (HTTP 200)
   meta: <meta name="robots" content="noindex,follow">
   -> FALLO: BLOQUEADO: el bloqueo de pruebas ha llegado a producción

6/6 casos detectados como se esperaba
```

`/olvidado/` es el fallo puesto a propósito (el `noindex` del entorno de pruebas que se queda en la web lanzada): el script lo marca como FALLO y la prueba cuenta como acierto que lo detecte.

```
== https://auramip.com/  (HTTP 200)
   sin bloqueos
   -> OK: indexable: bien para producción
== https://auramip.com/es/  (HTTP 200)
   sin bloqueos
   -> OK: indexable: bien para producción
== https://www.uncomo.com/  (HTTP 200)
   sin bloqueos
   -> OK: indexable: bien para producción

3/3 URLs como se esperaba
```

## Qué enseña

- **Tres formas buenas de cerrar un entorno de pruebas**: contraseña o IP (401/403, lo más seguro: ni rastrea ni indexa), `X-Robots-Tag: noindex` en la cabecera (vale para todo, también PDF e imágenes) y `<meta name="robots" content="noindex">`. Google recomienda el `noindex` en el HTML o en las cabeceras para el nombre de host temporal.
- **`robots.txt` con Disallow no es un bloqueo de indexación**: impide rastrear, no indexar, y además oculta el `noindex` a Google. Combinarlo con `noindex` es contradictorio.
- **El error del día del lanzamiento** es el contrario: el `noindex` o el `Disallow: /` del entorno de pruebas se copia a producción. Google lo menciona expresamente en sus guías de cambio de sitio («quitar cualquier bloqueo temporal»).

## Límites

- Solo una URL cada vez, sin JavaScript: un `noindex` inyectado por JS no aparece.
- `robotparser` de Python no implementa todos los matices del analizador de Google (comodines `*` y `$`). Para casos dudosos, el probador de `robots.txt` de Search Console.
- Algunos sitios responden distinto a un *user-agent* de Googlebot que no viene de las IPs de Google (bloqueo antibots); el resultado puede no ser el que ve Google.
