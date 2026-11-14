# Auditoría de datos estructurados de ecommerce

Ejercicio de la clase **Auditoría 04 · Auditando desde 0**. Lee el JSON-LD de una o varias páginas y lo compara con lo que Google exige para los fragmentos de producto, las fichas de comerciante (*merchant listings*), `BreadcrumbList` y `Organization`. Solo usa la biblioteca estándar de Python.

## Uso

```bash
python auditar_ld.py fixtures/producto-bien.html
python auditar_ld.py fixtures/producto-mal.html
python auditar_ld.py https://auramip.com/
python auditar_ld.py pagina-guardada.html otra.html https://...   # varias a la vez
```

Sale con código `0` si no hay errores y `1` si hay alguno (o si una URL no se pudo leer), así que sirve en un script por lotes.

## Qué comprueba

| Bloque | Errores (obligatorio) | Avisos (recomendado o sospechoso) |
|---|---|---|
| JSON | Bloque que no es JSON válido | Hay microdata o RDFa (Google recomienda JSON-LD) |
| Product | Sin `name`; sin `offers`/`review`/`aggregateRating`; sin `image` | Sin `description`, `brand`, `sku`, GTIN o `mpn`; `aggregateRating` presente (¿se ven las reseñas?) |
| Offer | Sin `price`; precio con símbolo o coma; sin `priceCurrency` o no ISO 4217 | `price` = 0 (no vale para ficha de comerciante); `availability` sin URL de schema.org; sin `itemCondition`, `priceValidUntil`, `url` |
| Envío y devoluciones | `shippingDetails` sin `shippingRate`, `shippingDestination` o `deliveryTime`; política sin `applicableCountry` o `returnPolicyCategory`; plazo finito sin `merchantReturnDays` | Ninguna de las dos ni en la oferta ni en la `Organization` |
| BreadcrumbList | Menos de 2 elementos; `ListItem` sin `position`, sin `name` o sin `item` (salvo el último) | Posiciones no correlativas |
| Organization y subtipos | (no tiene obligatorias) | Sin `name`, `url` o `logo`; estrellas sobre sí misma (reseñas autoatribuidas, sin estrellas) |

Requisitos sacados de la documentación de Google consultada el 2026-11-14 (enlaces en la nota de clase).

## Resultado probado (2026-11-14, Python 3.11.9)

- `fixtures/producto-bien.html`: `0 errores, 0 avisos`, exit `0`.
- `fixtures/producto-mal.html`: `7 errores, 15 avisos`, exit `1`. Los siete errores puestos a propósito: JSON roto, sin `image`, `aggregateRating` sin recuento, `"19,95 €"`, sin moneda, migas de un elemento y `ListItem` sin `position`.
- `https://auramip.com/`: 4 bloques (`LegalService`, `Attorney`, `FAQPage`, `WebSite`), `0 errores`; un aviso esperado porque la portada no es una ficha de producto.

## Límites

- **Muchas tiendas bloquean a los clientes que no son navegadores**: Allbirds, Ecoalf y Singularu (Shopify) devuelven 429 y Decathlon 403. En una auditoría real: rastrear con permiso del cliente (o desde una IP en su lista blanca), o guardar la página desde el navegador y pasarle el fichero.
- Lee el HTML que envía el servidor. Si el JSON-LD lo inyecta JavaScript, no lo verá: usar el HTML renderizado (Screaming Frog con renderizado JS, o guardar el DOM desde DevTools).
- No compara el marcado con lo que se ve en la página (precio visible, reseñas visibles): eso se revisa a mano o con extracción personalizada en Screaming Frog.
- No sustituye a la Prueba de resultados enriquecidos de Google: la complementa en lote.
