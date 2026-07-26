# Irmin Datos Estructurados

Ejercicio de la clase **06 — Crear código** (módulo Datos Estructurados).
Genera datos estructurados **JSON-LD** desde los datos reales del sitio, nunca escritos a mano.

## Tipos que emite

| # | Tipo | Dónde | De dónde salen los datos |
|---|---|---|---|
| 1 | **BlogPosting** | Entradas individuales (`is_singular('post')`) | `get_the_title()`, `get_the_date('c')`, autor, imagen destacada, etiquetas, categoría |
| 2 | **FAQPage** | Cualquier contenido con ≥2 encabezados que sean preguntas | Los propios `<h2>/<h3>/<h4>` del `post_content` → todo lo marcado **está visible** |
| 3 | **Product** | Donde se use el shortcode `[irmin_producto]` | Los atributos del shortcode, que además pintan la ficha visible |
| + | **BreadcrumbList** | Contenidos individuales | Jerarquía real: inicio → categoría/padre → título |
| + | **Organization + WebSite** | Todas las páginas | `get_bloginfo()` y el logo del customizer |

## Decisiones de implementación

- **Todo pasa por `wp_json_encode()`**, nunca por concatenación de cadenas: así es
  imposible generar una coma final, una comilla sin escapar o un acento roto
  (los errores de sintaxis de la clase 05).
- **Flags**: `JSON_UNESCAPED_UNICODE` (acentos legibles), `JSON_UNESCAPED_SLASHES`
  (URLs limpias) y `JSON_PRETTY_PRINT` (indentado, para poder leerlo en el código fuente).
- **`</` se convierte en `<\/`** antes de imprimir: un texto que contuviera
  `</script>` cerraría la etiqueta y rompería la página. El escape es válido en JSON.
- **Article, FAQ y migas van en el `<head>`**; **Product va en el `<footer>`**, que es
  lo recomendado para fichas de producto — el `<head>` se sirve primero y no conviene
  cargarlo, y además el shortcode se ejecuta dentro de `the_content`, o sea después
  de `wp_head`. Google lee el JSON-LD igual en `<body>` que en `<head>`.
- **Las propiedades opcionales solo aparecen si existen.** Ninguna propiedad vacía,
  ningún valor inventado.
- **Nada oculto**: cada dato marcado se ve en la página. Es requisito duro de Google,
  no una buena práctica.

## Convivencia con el tema (clase 07)

El tema `asdrubal` emite su propio `Car` y su `Organization` desde
`components/datos-estructurados.php`, porque solo él conoce sus campos ACF. Para
que no haya dos bloques del mismo tipo en la misma página —dos entidades
contradictorias— el plugin expone un filtro:

```php
add_filter( 'irmin_datos_estructurados_omitir', function ( $omitir, $tipo ) {
    return in_array( $tipo, array( 'organization', 'article' ), true ) ? true : $omitir;
}, 10, 2 );
```

Tipos que acepta: `organization`, `article`, `faq`, `product`, `breadcrumb`.
Con el tema asdrubal activo, el plugin sigue encargándose de FAQPage, Product y
BreadcrumbList; el tema se encarga del artículo/coche y de Organization.

## Uso del shortcode de producto

```
[irmin_producto
  nombre="Auditoría SEO técnica completa"
  descripcion="Rastreo, indexación, enlazado, sitemaps y datos estructurados."
  precio="890"
  moneda="EUR"
  disponibilidad="InStock"
  condicion="NewCondition"
  sku="CTS-AUD-01"
  marca="Climb The Searches"
  imagen="https://climbthesearches.com/img/auditoria.jpg"]
```

Pinta la ficha visible **y** alimenta el `Product` del footer con los mismos valores:
una sola fuente, así el precio del marcado nunca puede diferir del de la página.

El precio se normaliza automáticamente (`890`, `890,00 €` → `"890.00"`): número
limpio, punto decimal, sin símbolo de moneda. `availability` e `itemCondition` se
emiten con la **URL completa** de schema.org, como exige la especificación.

## Cómo probarlo

1. Activar el plugin en **Plugins → Irmin Datos Estructurados**.
2. Publicar una entrada con imagen destacada y con algún `<h2>` terminado en `?`
   seguido de su respuesta.
3. Ver el código fuente: los bloques `application/ld+json` aparecen en el `<head>`
   (y el de producto al final del `<body>`).
4. Validar:
   - **[validator.schema.org](https://validator.schema.org/)** → sintaxis y vocabulario.
   - **[Rich Results Test](https://search.google.com/test/rich-results)** → elegibilidad real en Google.
   - **[jsoncrack.com/editor](https://jsoncrack.com/editor)** → ver el árbol de entidades
     y comprobar de un vistazo que el anidamiento es el que se pretendía.

## `ejemplos/`

Los cuatro bloques ya generados, en ficheros sueltos, listos para pegar en los
validadores sin necesidad de levantar WordPress:

- `01-blogposting.json`
- `02-faqpage.json`
- `03-product.json`
- `04-breadcrumblist.json`

## Nota sobre FAQPage

**El resultado enriquecido de FAQ ya no existe.** Google lo restringió a webs de
gobierno y sanidad en agosto de 2023 y lo **retiró por completo el 7 de mayo de
2026**; en junio de 2026 borró la documentación y el tipo ya no aparece en la
galería de resultados enriquecidos.

El marcado se mantiene igualmente: sigue siendo schema.org válido, lo leen otros
buscadores y los LLMs, y describe la entidad de la página. Lo que no hace es
generar ninguna apariencia especial en Google.
