# Inventario de URLs y comparación de valores antes de migrar

Ejercicio de la clase **Migraciones 06-04 · URLs, plantilla y migración**. La clase monta dos hojas de Google Sheets: **«Localizar URLs»** (cada URL con su tipo: orgánica, referral, sin tráfico o sin catalogar, y si tiene enlaces) y **«Scraping de valores»** (title, description… de la URL actual frente a la nueva, con «Coincide» / «No coincide»). Este script hace las dos cosas con CSV, añade la comprobación que falta (que cada URL importante esté en el mapa de redirecciones) y avisa de la trampa de las mayúsculas.

| Fichero | Qué hace |
|---|---|
| `inventario.py` | Clasifica las URLs por prioridad (orgánico > referral > todas > sin catalogar), marca las que tienen enlaces, lista las URLs con tráfico o enlaces que no están en el mapa y compara title, description y h1 antiguos y nuevos |
| `datos/*.csv` | Datos de ejemplo inventados, con los nombres de la clase (`dominio.tld/ruta-1` → `ruta-67`…) y cuatro trampas puestas a propósito |

Solo librería estándar de Python.

## Ejecutar

```bash
python inventario.py                # datos de ejemplo
python inventario.py mis-datos/     # tus CSV, con los mismos nombres y cabeceras
```

Sale con código 0 si ha podido leer todos los CSV y 1 si falta alguno. Las cabeceras están en la docstring del script.

## Resultado (Python 3.11, 2026-11-02)

```
== 1. Inventario (Localizar URLs)
   URL                                              Tipo                                   Enlaces  Sesiones
   https://www.dominio.tld/ruta-1                   URL con tráfico orgánico               No       2272
   https://www.dominio.tld/ruta-2                   URL con tráfico orgánico               Sí       17512
   https://www.dominio.tld/Ruta-3                   URL con tráfico orgánico               No       2159
   https://www.dominio.tld/blog/guia                URL con tráfico orgánico               No       980
   https://www.dominio.tld/promo                    URL Referral                           Sí       2159
   https://www.dominio.tld/landing-afiliados        URL Referral                           No       640
   https://www.dominio.tld/ruta-3                   URL sin tráfico orgánico ni referral   No       0
   https://www.dominio.tld/aviso-legal              URL sin tráfico orgánico ni referral   No       0
   https://www.dominio.tld/etiqueta/vieja           URL sin tráfico orgánico ni referral   Sí       0
   https://www.dominio.tld/pdf/catalogo-2019.pdf    Sin catalogar                          Sí       0
   ! Solo cambian mayúsculas: https://www.dominio.tld/Ruta-3 / https://www.dominio.tld/ruta-3. Son URLs distintas; en Sheets, VLOOKUP las confunde

   Con tráfico o enlaces: 8. En el mapa de redirecciones: 4
   ! Falta redirección: https://www.dominio.tld/Ruta-3  (URL con tráfico orgánico, 2159 sesiones)
   ! Falta redirección: https://www.dominio.tld/landing-afiliados  (URL Referral, 640 sesiones)
   ! Falta redirección: https://www.dominio.tld/etiqueta/vieja  (tiene enlaces)
   ! Falta redirección: https://www.dominio.tld/pdf/catalogo-2019.pdf  (tiene enlaces)

== 2. Comparación de valores (Scraping de valores)
   https://www.dominio.tld/ruta-68          title        «la ruta 66 es mejor que la 67 o la 2» -> «La Ruta 66 es mejor que la 67 o la 2»  (solo cambian mayúsculas: Sheets diría «Coincide»)
   https://www.dominio.tld/ruta-69          title        «lo que sea de title» -> «un title random otra vez»  (No coincide)
   https://www.dominio.tld/guias/guia       description  «una description» -> «»  (vacío en la web nueva)

   Pares comparados: 6. Valores distintos: 3 (de ellos, 1 que Sheets daría por iguales)
```

## Qué enseña

- **La prioridad importa**: `ruta-2` tiene tráfico orgánico y referral; cuenta como orgánica, igual que en la fórmula de la clase (el primer `IFERROR` que acierta gana).
- **Lo que no tiene tráfico también cuenta**: `etiqueta/vieja` y el PDF no traen visitas pero **tienen enlaces externos**; sin redirección, esos enlaces se pierden. El PDF ni siquiera aparece en el rastreo («Sin catalogar»): solo se sabe de él por los enlaces.
- **La trampa de las mayúsculas**: `/Ruta-3` y `/ruta-3` son URLs distintas (la ruta distingue mayúsculas), pero en Google Sheets `=` y `VLOOKUP` no las distinguen. En la hoja, el mapa con `ruta-3` haría que `/Ruta-3`, la que tiene 2.159 sesiones, pareciera cubierta. Lo mismo pasa con el title de `ruta-68`: la hoja diría «Coincide». En Sheets, `EXACT()` sí distingue mayúsculas.
- **Un campo vacío es un cambio**: la description de la guía ha desaparecido en la web nueva.

## Límites

- Los datos son inventados. Con datos reales, cada CSV sale de su herramienta: Search Console o GA4 (orgánicas, referral), Screaming Frog o sitemaps (todas, crawls), Search Console o Ahrefs (enlaces).
- Compara texto exacto tras quitar espacios de los extremos; no normaliza URLs (barra final, parámetros, `http`/`https`). Normalizarlas antes, con el mismo criterio para todas las fuentes.
- No sigue redirecciones: para comprobar que el mapa está implementado, `saltos.py` ([../tipos-de-migraciones/](../tipos-de-migraciones/)).
