# Meta robots cambiada con JavaScript

Ejercicio de **Renderización, clase 03 Web Rendering Service**.

En clase se vio el aviso legal de Carlos: el servidor mandaba `<meta name="robots" content="none">` y un script lo cambiaba a `all`. La conclusión: **Google no llega a ver el `all`**, porque al encontrar `noindex` en el HTML puede saltarse el renderizado. Este ejercicio monta ese caso y el contrario, y compara lo que hay en el HTML recibido con lo que queda en el DOM.

## Ficheros

| Fichero | Qué hace |
|---|---|
| [`noindex-a-index.html`](noindex-a-index.html) | El caso de clase: `none` en el HTML, el script de Carlos tal cual lo cambia a `all` |
| [`index-a-noindex.html`](index-a-noindex.html) | El contrario: `all` en el HTML, un script lo cambia a `noindex` |
| [`veredicto.php`](veredicto.php) | Lee la meta robots del HTML (HTTP) y del DOM (Chrome headless `--dump-dom`) y dice qué haría un bot sin JS y qué haría Google. Sale con 1 si algún resultado no es el esperado |

## Cómo probarlo

Con el PHP de Laragon (8.x) y Chrome instalado:

```bash
cd ejercicios/renderizacion-meta-robots-js
php -S 127.0.0.1:8091          # en una terminal
php veredicto.php              # en otra
```

Salida real (2026-10-25):

```
noindex-a-index.html
  HTML recibido : none
  DOM renderizado: all
  bot sin JS     : noindex
  Google         : noindex  (puede no renderizar: el cambio a index no llega a verse)
  OK

index-a-noindex.html
  HTML recibido : all
  DOM renderizado: noindex
  bot sin JS     : index
  Google         : noindex  (renderiza y aplica el DOM)
  OK

2/2 páginas con el resultado esperado
```

**Lo que está medido y lo que no.** «HTML recibido» y «DOM renderizado» son mediciones reales. Las filas «bot sin JS» y «Google» aplican reglas: la primera solo mira el HTML; la segunda, la documentación de Google (*«When Google encounters the noindex tag, it may skip rendering»*; *«If you do want the page indexed, don't use a noindex tag in the original page code»*). Para ver lo que hace Googlebot de verdad hay que publicar las páginas y usar la Inspección de URL.

## Qué enseña

- **`noindex` → `index` con JS no funciona en Google.** Lo que manda en el HTML es la `noindex`, y con ella Google puede no renderizar. Las dos direcciones acaban igual para Google: no indexada.
- **`index` → `noindex` con JS divide a los bots.** Google, que renderiza, verá `noindex`. Un bot que no ejecuta JS (muchos rastreadores de IA) se queda con `all`. Si de verdad no quieres que se indexe, ponlo en el HTML o en la cabecera `X-Robots-Tag`.
- **`none` equivale a `noindex, nofollow`; `all` es el valor por defecto** y escribirlo no cambia nada. El script de clase no «activa» la indexación: solo quita el bloqueo, y demasiado tarde.
