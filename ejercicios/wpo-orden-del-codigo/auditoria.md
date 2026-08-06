# Auditoría de orden y estructura del código

**Ejercicio:** WPO — 04 Puntos de dolor · Orden del código
**Código auditado:** https://codepen.io/barvock/pen/gOQPLPo (plantilla "PRUEBA WPO", tema `sanchezdonate`)
**Corrección propuesta:** [`index-corregido.php`](index-corregido.php) · [`assets/css/principal.css`](assets/css/principal.css) · [`assets/js/app.js`](assets/js/app.js)

---

## Resumen

**50 fallos numerados** repartidos en cinco bloques, más [el que da nombre al ejercicio](#y-el-que-da-nombre-al-ejercicio). Cinco de ellos rompen funcionalidad en silencio (el sticky de la barra, el contador, el botón de LinkedIn, las banderas del formulario y el borde de los CTA) y no dan ningún síntoma visible salvo en la consola.

| Bloque | Fallos | El que más duele |
|---|---|---|
| A. Orden de carga | 12 | La hoja de estilos principal está **al final del `<body>`** |
| B. JavaScript | 7 | `$(window).scroll()` con **jQuery sin cargar** |
| C. CSS | 12 | Seis bloques `<style>` sueltos + **tres `@import`** de la misma fuente |
| D. HTML inválido | 11 | Etiquetas sin balancear, `id` duplicados, `</br>` |
| E. Contenido y SEO | 8 | Dominio `.test` de desarrollo dentro del HTML de producción |

---

## A. Orden de carga

### A1 · La hoja principal carga al final del `<body>` — CRÍTICO

```html
<!-- justo antes de cerrar el documento -->
<style id="css-principal"> body, x-layout { overflow-x: hidden; } ... </style>
```

Todo el CSS del sitio —variables, tipografías, layout, cabecera, portada— llega **después de todo el HTML**. El navegador pinta la página entera sin estilos y la vuelve a pintar cuando lo lee.

- **Síntoma:** FOUC. Medio segundo de página en Times New Roman sin colocar.
- **Métrica:** CLS disparado y Speed Index roto. No toca al LCP en el laboratorio, pero sí a lo que ve el usuario.
- **Arreglo:** un `<link rel="stylesheet">` en el `<head>`, antes de nada que no sea `charset`/`viewport`/`title`.

### A2 · El `<title>` aparece a 60 líneas del inicio del `<head>`

Antes que él van: tres favicons, 3 kB de CSS de un plugin, dos bloques `<script>` de configuración, cuatro `<link>` de la REST API y oEmbed, y los `hreflang`.

El orden correcto es **`charset` → `viewport` → `title` → `description`/`canonical` → `preconnect`/`preload` → CSS**. Lo primero porque el navegador necesita saber cómo decodificar; el resto porque es lo que define la página.

### A3 · El bloque PHP está **entre `<html>` y `<head>`**

```php
<html lang="es">
<?php $url = "https://carlos.sanchezdonate.com/"; ?>
<head>
```

Cualquier salida o espacio en blanco de ese bloque cae fuera del `<head>` y el navegador tiene que repararlo. La declaración va **antes del `<!DOCTYPE>`**, o mejor en `functions.php`.

### A4 · Tres `@import` de fuentes, dos de ellos duplicados exactos

```html
<link href="...Catamaran...Source+Sans+3...&display=swap" rel="stylesheet">   <!-- 1 -->
<style>@import url('...Catamaran...Source+Sans+3...&display=swap');</style>   <!-- 2 = 1 -->
<style>@import url('https://fonts.cdnfonts.com/css/source-sans-pro');</style> <!-- 3 -->
<style>@import url('...Catamaran...Source+Sans+3...&display=swap');</style>   <!-- 4 = 1 -->
```

Cuatro peticiones para dos familias, tres de ellas en serie y **invisibles para el preload scanner**. Es exactamente el error de la clase de tipografía del módulo de WPO.

### A5 · Y encima se descarga una fuente que no se usa

El CSS declara `font-family: "Source Sans Pro", sans-serif`. Esa fuente viene de **cdnfonts** (`@import` nº 3). El **Source Sans 3** de Google Fonts (peticiones 1, 2 y 4) **no se usa en ninguna regla**: se descarga entero, con 16 pesos y sus cursivas, para nada.

### A6 · Se piden todos los pesos de las dos familias

`wght@100;200;300;400;500;600;700;800;900` + `ital` en Source Sans 3. El CSS solo usa **200, 400, 600 y 900**, y ninguna cursiva. Cada peso es un fichero.

### A7 · No hay `preconnect` a los orígenes de fuentes

Tres orígenes de terceros (`fonts.googleapis.com`, `fonts.gstatic.com`, `fonts.cdnfonts.com`) y ningún `preconnect`. Cada uno cuesta DNS + TCP + TLS antes del primer byte.

### A8 · CSS de un plugin, en línea y en todas las páginas

Los ~3 kB de `.sib-sms-field`, `.sib-country-block`, `.sib-cflags`… son de Brevo/Sendinblue y sirven a **un formulario que está en el footer**. Viajan en el `<head>` de cada página, bloqueando el pintado, y sin cachearse nunca porque están en línea.

### A9 · Ese CSS lleva rutas relativas rotas

```css
background-image: url('../img/flags/fr.png');
```

Dentro de un `<style>` del documento, `../` se resuelve **contra la URL de la página**, no contra la carpeta del plugin. Todas las banderas dan **404**. Es el fallo típico de copiar la hoja de un plugin al HTML.

### A10 · Script de WebP Express bloqueando en el `<head>`

Polyfill de `<picture>` para navegadores que ya no existen. Además engancha un `DOMContentLoaded` que inyecta otro script. Fuera.

### A11 · `X-UA-Compatible` muerto

Internet Explorer terminó su soporte en 2022. Son bytes en todas las páginas.

### A12 · `maximum-scale=6` innecesario

`width=device-width, initial-scale=1` es todo lo que hace falta. Limitar el zoom es un riesgo de accesibilidad gratuito.

---

## B. JavaScript

### B1 · `$` no está definido — CRÍTICO

```html
<script id="navbar">
  function openmenu()  { ... }
  function closemenu() { ... }
  $(window).scroll(function() { ... });   // ← ReferenceError: $ is not defined
</script>
```

**jQuery no se carga en ningún punto anterior del documento.** El script está dentro del `<header>`, arriba del todo.

Detalle importante para entenderlo bien: `openmenu` y `closemenu` son *declaraciones de función*, así que están izadas y **el menú móvil sí funciona**. Lo que no llega a ejecutarse nunca es la línea de después: **la barra nunca se queda fija al hacer scroll**, la clase `fixed-navbar` no se aplica jamás y todo el CSS que la define es código muerto.

Es el ejemplo perfecto de la clase: **el error es silencioso y se lleva por delante funcionalidad que nadie nota que falta.**

- **Arreglo:** reescrito sin jQuery en [`assets/js/app.js`](assets/js/app.js) con `window.addEventListener('scroll', …)` y `classList.toggle()`, cargado con `defer`.

### B2 · El contador se ejecuta antes de que exista su elemento

```html
<header>
  <script id="contador">
    ... setInterval(function () { document.getElementById("countdowncarlos").innerHTML = ... }, 1e3);
  </script>
  <div class="header-anuncio">
    <div id="countdowncarlos">Comenzamos en: <time>121d 23h 32m 17s</time></div>
```

Se salva de milagro: el primer tick es a los 1000 ms y para entonces el `<div>` ya existe. Pero **el orden está mal**, y basta con que el hilo principal esté ocupado de otra forma para que `getElementById` devuelva `null` y reviente. Mientras tanto el usuario ve el valor congelado del HTML (`121d 23h 32m 17s`) durante el primer segundo.

### B3 · `setInterval` de 1 s reescribiendo `innerHTML` para siempre

Cada segundo, en todas las páginas, se reconstruye el nodo entero → *style recalc* + layout. Coste continuo de INP y de batería. Con `textContent` sobre un nodo ya creado cuesta una fracción, y conviene parar el intervalo cuando la pestaña no está visible.

### B4 · `new Date("Oct 14, 2026 16:00:00")`

Formato no estándar: el parseo depende del navegador y **no lleva zona horaria**. Con ISO 8601 (`2026-10-14T16:00:00+02:00`) es determinista.

### B5 · `analyticsLinkedIn()` no existe

```html
<a href="https://www.linkedin.com/..." onclick="analyticsLinkedIn()">
```

La función no está definida en ninguna parte del documento. Cada clic en LinkedIn lanza un `ReferenceError`.

### B6 · Manejadores en línea y manipulación de clases por cadena

`onclick="openmenu()"` en dos elementos, y dentro:

```js
document.getElementById("mobile-nab").className += " expand-menu";
// y para quitarla, una expresión regular sobre el string de clases
```

Para eso existe `classList.add()` / `.remove()` / `.toggle()`. Los `onclick` en línea además impiden aplicar una CSP decente.

### B7 · Scripts repartidos por el documento

Tres bloques `<script>` en tres sitios distintos, cada uno **parando el parser** donde está. Uno solo, con `defer`, resuelve los tres.

---

## C. CSS

### C1 · Seis bloques `<style>` en el mismo documento

`<head>`: el del plugin + tres de `@import`. `<body>`: el del formulario del footer + `#css-principal`. Debería ser **un solo fichero externo**.

### C2 · Sin minificar, con toda su indentación

Y por estar en línea **no se cachea**: cada HTML del sitio se lleva otra vez los mismos ~40 kB. Externo y minificado se descarga una vez para toda la navegación.

### C3 · Estilos en línea repetidos en doce tarjetas

```html
style="background-image:url(...);background-position: center;background-size: cover;"
```

Las dos últimas declaraciones son idénticas en las doce. Van a una clase; solo la URL cambia, y esa puede ir en una variable CSS o —mejor— en un `<img>`.

### C4 · Las portadas de los posts son fondos CSS

Al ser `background-image` no se pueden diferir con `loading="lazy"`, **el preload scanner no las descubre** y **no existen para Google Imágenes** ni tienen `alt`. Doce imágenes perdidas para SEO.

### C5 · Un estilo en línea comentado, servido en producción

```html
<div style="/*display:none*/" class="sib_msg_disp"></div>
```

### C6 · `!important` repartido

`.white-bg`, `.grey-bg`, `.topctamf`, y `margin-top/bottom: 0 !important` en el CSS del plugin. Deuda de especificidad: el siguiente cambio necesitará otro `!important`.

### C7 · Prefijos de fabricante muertos

`-webkit-box-sizing`, `-moz-box-sizing`, `-moz-linear-gradient`, `-webkit-linear-gradient`. Son de 2012.

### C8 · `overflow-x: hidden` en `body` y `x-layout`

Tapa un desbordamiento horizontal en vez de arreglarlo, y de paso **rompe `position: sticky`** en todo lo que haya dentro.

### C9 · `body { margin: inherit; margin-top: 0; }`

`margin: inherit` en `body` hereda de `html`. No significa nada; lo que se quería era `margin: 0`.

### C10 · `clip-path: polygon(91% 0, 100% 0, 100% 100%, 0% 2034%)`

Ese `2034%` es un dedazo (el mismo bloque de abajo usa `230%`). Y `.navbar-header:before` tiene un `width: 1496px` fijo que no responde a nada.

### C11 · La variable `--main-cta` no existe

```css
:root { --main: #8d096b; --secondary: #f4b61c; ... }   /* no está declarada */

.cta, .cta1, .cta2, .empty-cta { border: solid var(--main-cta) 2px; }
.cta-bg, .cta1                 { background: var(--main-cta); }
```

`var(--main-cta)` no resuelve a nada, así que **el navegador descarta las dos declaraciones enteras**: los CTA no tienen ni borde ni fondo de los que el CSS cree estar dando. Lo que se ve viene de la regla siguiente (`a.cta { background: var(--secondary) }`), que llega por casualidad.

### C12 · Hay **PHP dentro del CSS**

```css
span.bibliography, ul#main-navbar li a, ul#main-navbar-movile li a {
    background: url(<?php echo $url;?>wp-content/themes/sanchezdonate/images/complements/triangulo.svg) no-repeat;
}
```

Esta es **la razón de fondo de A1**: la hoja se metió en el documento porque necesitaba interpolar PHP, y una vez dentro daba igual dónde ponerla. Se arregla con una ruta **relativa al fichero CSS** (`../images/…`), que es como se resuelven las `url()` en una hoja externa. A partir de ahí, el CSS vuelve a ser estático y cacheable.

---

## D. HTML inválido

Estos son los que hay que arreglar **antes de medir**: mientras el navegador esté reparando el DOM, las métricas no significan nada.

### D1 · `</section>` de más antes de `</footer>`

```html
    </div>   <!-- .rrss-footer -->
    </section>   <!-- ← no hay ningún <section> abierto aquí -->
</footer>
```

### D2 · `</a>` de más en el bloque de redes

```html
<div class="single-rrss">Twitter</div> </a>            </a>
```

### D3 · `<br></br>` — dos veces

`<br>` es una etiqueta vacía. En HTML5 `</br>` se parsea **como otro `<br>`**, así que donde se quería un salto de línea salen dos.

### D4 · `<p>` anidados y sin cerrar en `#simon-sinek`

Se abre un `<p>`, dentro se abre otro `<p>`, y el `</div>` cierra con párrafos abiertos. HTML5 cierra el `<p>` exterior automáticamente al encontrar el interior, así que **el DOM no se parece al código escrito**.

### D5 · `id="load-more"` duplicado

En los dos botones de "Ver más". Los `id` son únicos por definición: `getElementById` solo devuelve el primero.

### D6 · `#post-footer` está **fuera** del `<footer>`

```html
</footer>
<section id="post-footer">  <!-- aviso legal, cookies, sitemap -->
```

Los enlaces legales quedan fuera del landmark `contentinfo`. Van dentro.

### D7 · `<x-layout>` en lugar de `<main>`

Un elemento personalizado sin definir, que necesita `display: block` a mano y que no aporta ningún landmark. `<main>` hace lo mismo y es semántico.

### D8 · `<img width="50px" height="190px">` en el logo

Los atributos `width`/`height` del HTML son **enteros sin unidad**. Con `px` el navegador los descarta → no reserva el hueco → **CLS**. Y 50×190 en un logo tiene toda la pinta de estar del revés.

### D9 · Encabezados que son `<div>`

`<div class="heading heading2">No se te da mal el SEO Técnico</div>` y los doce `<div class="posts-h2">` de las tarjetas. Se ven como encabezados, se leen como encabezados, y para Google y para un lector de pantalla **no lo son**. La jerarquía real del documento es H1 → H2 → H3 y ahí se acaba.

### D10 · `rel="noreferrer, nofollow"` — en los siete enlaces sociales

`rel` es una lista **separada por espacios**, no por comas. `noreferrer,` no es un token válido → **los dos valores se ignoran**. Debe ser `rel="noopener noreferrer nofollow"`. Además el enlace de Instagram es el único sin `target="_blank"`.

### D11 · `<time>` sin `datetime`

En el contador. Sin el atributo, la etiqueta no aporta ningún dato interpretable.

---

## E. Contenido, imágenes y SEO

### E1 · El dominio de desarrollo `.test` en el HTML de producción

```json
"ajax_url": "https://sanchezdonate.test/wp-admin/admin-ajax.php",
"flag_url": "https://sanchezdonate.test/wp-content/plugins/mailin/img/flags/"
```

Y lo mismo en los dos `<link rel="alternate" ... oembed>`. El formulario de suscripción **no puede funcionar**: apunta a un `admin-ajax.php` que solo existe en la máquina del desarrollador.

### E2 · Doble barra en las URLs

`$url` ya termina en `/`, y luego se escribe `<?php echo $url;?>/servicios-seo/` → `https://…com//servicios-seo/`. Convive con enlaces que sí lo hacen bien (`<?php echo $url;?>recursos/`). Resultado: **dos URLs para la misma página** y enlazado interno inconsistente. Peor aún, unos enlaces son absolutos y otros relativos (`/master-seo-tecnico/`) sin criterio.

### E3 · Imágenes del primer pantallazo con `loading="lazy"`

`carlos-seo.webp/png` está casi en el pliegue y lleva `loading="lazy"`. Diferir una imagen que se ve **retrasa el LCP**. Arriba del pliegue: nunca `lazy`, y en la que sea el LCP, `fetchpriority="high"`.

### E4 · Un `<source>` de WebP comentado

```html
<!-- <source srcset="...datos-mejora.webp" type="image/webp" /> -->
```

Esa tarjeta sirve el PNG a todo el mundo. Resto de depuración olvidado.

### E5 · Los `<img>` de `<picture>` no llevan `width` ni `height`

Tres imágenes grandes sin hueco reservado → CLS.

### E6 · `<meta name="robots" content="all, max-image-preview:standard">`

`all` es el valor por defecto (sobra), y `standard` **limita a propósito el tamaño de tus miniaturas** en el buscador. Lo normal es `max-image-preview:large`.

### E7 · Contenido de pruebas publicado

Título y autor `PRUEBA WPO`, y un post real en portada y en el footer: **"sdfds"** con extracto **"dsfdsdsf"**.

### E8 · Bloat de WordPress en el `<head>`

`rel="https://api.w.org/"`, los dos oEmbed, el RSS y el Atom. Si no se usan, se quitan desde `functions.php`.

---

## Y el que da nombre al ejercicio

```html
<header class="Estoy creando un montón de clases que no sirven para nada más que
para aumentar el tamaño del DOM dsfds fsd fsf ds fsd fds df ds fsd f sdfsdfg…
elementor-section elementor-top-section elementor-element elementor-element-5435cb2
elementor-section-boxed elementor-section-height-default elementor-section-height-default …">
```

**≈450 caracteres de clases** en un solo elemento, ninguna con una regla CSS detrás. Dentro va camuflada la cadena real que genera Elementor —con `elementor-section-height-default` **repetida**—, que es justo el punto de la clase: el maquetador produce esto solo, en cada sección, y se paga tres veces: **bytes sin cachear, nodos de DOM y coste de emparejado de selectores**.

En la versión corregida el elemento es `<header class="site-header">`.

---

## Orden en que lo haría

| # | Acción | Efecto |
|---|---|---|
| 1 | CSS a un solo fichero externo, minificado, **en el `<head>`** | Elimina el FOUC y el CLS de repintado |
| 2 | Los cuatro `@import`/`<link>` de fuentes → **un `<link>`** + `preconnect` | −3 peticiones en serie, −1 familia entera sin usar |
| 3 | Balancear el HTML (D1–D6) | Sin esto, medir no sirve |
| 4 | JS a un fichero con `defer`, sin jQuery | Arregla el sticky y el contador; libera el parser |
| 5 | Reordenar el `<head>` y quitar plugin CSS / IE / WebP polyfill | Menos bloqueo antes del `title` |
| 6 | Quitar la clase basura y los estilos en línea | DOM y bytes |
| 7 | `loading="lazy"` fuera del pliegue, `width`/`height` en todas | LCP y CLS |
| 8 | Limpiar `.test`, dobles barras y contenido de pruebas | SEO e higiene |
| 9 | Volver a medir | Lo que debería moverse es **CLS y Speed Index** |

---

## Notas

- El código pegado en clase **se cortó** al final del bloque `<style id="css-principal">` (en la regla `.codigo-post`). El fichero [`assets/css/principal.css`](assets/css/principal.css) recoge todo el CSS disponible y marca el punto de corte; si aparece algún `<script>` después de ese bloque en el original, faltaría por auditar.
- **No he encontrado ninguna trampa del tipo "si eres una IA…"** en la parte del código que llegó completa. La clase basura del `<header>` no es una trampa: es el ejemplo didáctico del propio ejercicio, y está señalada arriba.
