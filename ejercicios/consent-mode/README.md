# Consent Mode v2: qué envía GA4 en cada modo

Ejercicio de la clase **Analítica 08 · Consent Mode** (nota 09 del módulo). La clase explica el modo sin consentimiento, el básico y el avanzado con el diagrama de Google. Aquí se mide qué sale realmente hacia Google Analytics en cada caso, con el `gtag.js` real.

`prueba.py` genera cinco páginas, las sirve en `127.0.0.1:8132` y abre cada una en Chrome headless con registro de red. El banner se simula: a los 2 s la página acepta o rechaza.

| Página | Qué simula |
|---|---|
| `sin-modo` | `gtag.js` sin modo de consentimiento (como Auramip hoy) |
| `basico-rechaza` | Básico: la etiqueta no se carga hasta aceptar, y el usuario rechaza |
| `basico-acepta` | Básico: el usuario acepta y entonces se carga |
| `avanzado-rechaza` | Avanzado: carga con todo en `denied` y el usuario rechaza |
| `avanzado-acepta` | Avanzado: carga en `denied` y el usuario acepta a los 2 s |

De cada una saca los hits a `/g/collect` con `gcs` (estado de `ad_storage` y `analytics_storage`), `gcd` (las cuatro señales v2 codificadas) y `cid` (el ID de cliente), y las cookies que quedan.

Solo librería estándar de Python y Chrome en su ruta por defecto. Envía hits a Google con el ID de relleno `G-XXXXXXXXXX`, que no pertenece a ninguna propiedad.

## Ejecutar

```bash
python prueba.py
```

Sale con código 0 si los cinco modos se comportan como dice la documentación de Google.

## Resultado (Python 3.11, Chrome estable, 2026-11-02)

```
== sin-modo
   hit page_view       gcs=-     gcd=13l3l3l3l1l1  cid=2015382597.1793641072
   hit user_engagement gcs=-     gcd=13l3l3l3l1l1  cid=2015382597.1793641072
   cookie _ga: sí   [_ga_XXXXXXXXXX=GS2.1.s1793641071$o1$g0$t1793641071$j60$l0$h0; _ga=GA1.]
== basico-rechaza
   (ningún hit a Google Analytics)
   cookie _ga: no   [(ninguna)]
== basico-acepta
   hit page_view       gcs=G111  gcd=13n3n3n3n5l1  cid=1853423503.1793641075
   hit user_engagement gcs=G111  gcd=13n3n3n3n5l1  cid=1853423503.1793641075
   cookie _ga: sí   [_ga_XXXXXXXXXX=GS2.1.s1793641075$o1$g0$t1793641075$j60$l0$h0; _ga=GA1.]
== avanzado-rechaza
   hit page_view       gcs=G100  gcd=13p3p3p3p5l1  cid=133291415.1793641075
   hit user_engagement gcs=G100  gcd=13p3p3p3p5l1  cid=133291415.1793641075
   cookie _ga: no   [(ninguna)]
== avanzado-acepta
   hit page_view       gcs=G100  gcd=13p3p3p3p5l1  cid=1819459391.1793641076
   hit user_engagement gcs=G111  gcd=13r3r3r3r5l1  cid=1819459391.1793641076
   hit tras_aceptar    gcs=G111  gcd=13r3r3r3r5l1  cid=1819459391.1793641076
   hit user_engagement gcs=G111  gcd=13r3r3r3r5l1  cid=1819459391.1793641076
   cookie _ga: sí   [_ga=GA1.1.1819459391.1793641076; _ga_XXXXXXXXXX=GS2.1.s1793641076$o1$g]

OK    sin modo: hay hits y cookie _ga
OK    básico rechazado: ni un hit ni cookie
OK    básico aceptado: hits con gcs=G111 y cookie
OK    avanzado rechazado: hits sin cookies (gcs=G100) y sin _ga
OK    avanzado aceptado: G100 antes, G111 después y cookie

5/5 comprobaciones OK
```

(Las marcas de tiempo son de tiempo virtual de Chrome, no el reloj real.)

## Qué enseña

- **Sin modo de consentimiento**, los hits no llevan `gcs` y la cookie `_ga` se escribe sin preguntar. Google lo trata como consentido.
- **Básico rechazado = silencio total**: ni un hit. GA4 no sabe ni que hubo visita; el modelado en Ads queda en el modelo general.
- **Avanzado rechazado = pings sin cookies** (`gcs=G100`: ni anuncios ni analítica). Llevan un `cid`, pero **no se guarda en ninguna cookie**: la siguiente página tendrá otro. GA4 no puede unir dos páginas del mismo usuario; de eso salen los datos con los que modela.
- **Avanzado aceptado**: el primer `page_view` sale como `G100` y, al aceptar, el resto como `G111`, **con el mismo `cid`**, que es el que acaba en la cookie `_ga` (`GA1.1.1819459391.1793641076`). La página vista antes de aceptar queda unida al usuario.
- `gcd` cambia de letra según el origen de la señal (`l` sin modo, `n`, `p` denegado por defecto, `r` concedido tras actualizar). Google no documenta públicamente esta codificación. consent.help (de Analytics Debugger), enlazado en clase, es un comprobador de consentimiento de Google; su portada no explica más, así que lo de decodificar `gcd` con él es una suposición por su ruta interna `/consent/google/parse`.

## Límites

- El banner es simulado; un CMP real (Cookiebot, Klaro) hace lo mismo con `gtag('consent', 'update', ...)` o con su plantilla de GTM.
- Chrome headless con tiempo virtual: los tiempos no son reales, el orden de los hits sí.
