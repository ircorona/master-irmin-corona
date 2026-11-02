# Instalación de GA4: cuánto pesa cada método y cómo detectarlo

Ejercicio de la clase **Analítica 07 · Instalación de GA4**. La clase da tres métodos (Google Tag Manager, recomendado; etiqueta nativa, no recomendada; plugin del CMS). Aquí se mide lo que cada uno hace descargar al navegador y se detecta cuál usa una web real.

| Fichero | Qué hace |
|---|---|
| `peso.py` | Descarga en vivo `gtag.js` y `gtm.js` de googletagmanager.com (con IDs de relleno: Google sirve el código base igual) y suma lo que descarga cada método |
| `red.py` | Abre una web en Chrome headless con registro de red y dice qué scripts de Google pide: GTM + etiqueta de Google, solo GTM, solo `gtag.js` o nada |

Solo librería estándar de Python, más `curl` (viene con Windows 10/11 y Git Bash) y Chrome en su ruta por defecto.

## Ejecutar

```bash
python peso.py                          # pesos y comparación por método
python red.py                           # simoahava.com y auramip.com
python red.py https://www.ejemplo.com/  # cualquier web
```

Los dos salen con código 0 si todo va bien y 1 si algo falla.

## Resultado (Python 3.11, Chrome estable, 2026-11-02)

```
gtag.js  HTTP 200  transferido  150.1 KB (Brotli)  JS  435.6 KB  https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX
gtm.js   HTTP 200  transferido   86.4 KB (Brotli)  JS  238.3 KB  https://www.googletagmanager.com/gtm.js?id=GTM-XXXXXXX

Por método
  Etiqueta nativa (gtag.js en el <head>)   gtag.js             150.1 KB transferidos,  435.6 KB de JS
  GTM con etiqueta de Google dentro        gtm.js + gtag.js    236.5 KB transferidos,  673.8 KB de JS
  Plugin que inserte gtag.js (p. ej. Site Kit) gtag.js         150.1 KB transferidos,  435.6 KB de JS

GTM añade 86.4 KB transferidos (+58 %) frente al nativo, con el contenedor vacío. Cada etiqueta que se añada a GTM suma más.
OK
```

```
== https://www.simoahava.com/
   gtag/js  G-L7ZN1XEEW4
   gtm.js   GTM-PZ7GMV9
   -> GTM con etiqueta de Google clásica (gtm.js + gtag.js)
== https://auramip.com/
   gtag/js  G-5ZSYYZWBC5
   -> Etiqueta nativa o plugin (solo gtag.js)

2/2 webs revisadas
```

## Qué enseña

- **GTM no es gratis en peso.** La etiqueta de Google dentro de GTM sigue descargando `gtag.js`: GTM añade su propio `gtm.js` encima (+86 KB transferidos, +238 KB de JS que analizar con el contenedor vacío). La clase lo recomienda por gestión (versiones, permisos, etiquetas sin tocar código), no por rendimiento.
- **simoahava.com** confirma el patrón clásico: `gtm.js` y después `gtag/js?id=G-...`.
- **Desde el 12-10-2026 hay contenedores optimizados** (voluntario, según las notas de la versión de Tag Manager): envían a GA4 sin cargar `gtag.js`, así que una web con GTM puede pedir solo `gtm.js` y aun así medir. `red.py` lo marca como «solo gtm.js» y pide mirar si hay hits a `/g/collect`. El peso de un contenedor optimizado no está medido aquí (haría falta uno real).
- **auramip.com** lleva GA4 con etiqueta nativa en el `<head>` y `gtag('config')` directo, sin modo de consentimiento.

## Límites

- Los pesos son los del código base con IDs inexistentes. Un contenedor real con etiquetas pesa más; un `gtag.js` real puede variar algo según la configuración de la propiedad.
- `red.py` no acepta banners de cookies: lo que espera al consentimiento no aparece.
- El mecanismo exacto de Site Kit no está en su ficha de WordPress.org; se asume que inserta `gtag.js`. Para comprobarlo, pasar `red.py` por una web que lo use.
