# Vue y SEO: lo que llega en el HTML del servidor

Ejercicio de **Frameworks y Tecnologías, clase 10 Vue**.

Una página de producto en **Vue 3.5.44** con **Vue Router 5.4.0**, renderizada en servidor con `renderToString` (`vue/server-renderer`), frente a la SPA que genera `create-vue` por defecto. Prueba las tres trampas de Vue que señala su documentación.

## Cómo probarlo

Git Bash, con Node.js (probado con 24.12):

```bash
cd ejercicios/vue-seo
npm install          # solo la primera vez (node_modules/ fuera de git)
node prueba.mjs
```

Salida real (2026-10-25):

```
│ variante               │ h1 │ v-show (oculto) │ v-if (oculto) │ async setup │ onMounted │ <a href> │
│ 'CSR (create-vue)'     │ 0  │ false           │ false         │ false       │ false     │ 0        │
│ 'SSR (renderToString)' │ 1  │ true            │ false         │ true        │ false     │ 1        │

HTML del SSR:
<main>
<h1>Zapatillas de trail</h1>
<p id="precio-setup">PRECIO-ASYNC-SETUP 89 EUR</p>
<p id="precio-mounted">Cargando...</p>
<section>
<h2>Preguntas</h2>
<p id="resp-vshow" style="display:none;">RESPUESTA-CON-V-SHOW</p>
<!---->
</section>
<nav>
<a href="/categoria/trail/" class="">Más de trail</a>
<span>Ofertas (custom)</span>
</nav>
</main>

6/6 comprobaciones OK
```

## Qué enseña

- **`v-show` deja el contenido en el HTML; `v-if`, no.** Un acordeón cerrado con `v-show` envía la respuesta con `display:none` (Google la lee, como pestañas y acordeones permitidos). Con `v-if` solo queda un comentario `<!---->`: la respuesta no existe hasta que alguien abre el acordeón.
- **El SSR espera a `async setup()`, no a `onMounted()`.** `onMounted` solo se ejecuta en el navegador, así que el bot recibe «Cargando...». Es el «síncrono» del aviso de Vue: el rastreador no espera a lo que llega por AJAX.
- **`<RouterLink>` genera un `<a href>` real**, salvo con la prop `custom`, que no envuelve en `<a>`: ahí queda un `<span>` con un clic, que no es un enlace.
- **La SPA de `create-vue` entrega `<div id="app"></div>`**: cero contenido sin JS.
