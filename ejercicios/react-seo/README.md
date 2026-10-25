# React y SEO: la misma página, cinco formas de servirla

Ejercicio de **Frameworks y Tecnologías, clase 09 React**.

Una página de producto en **React 19.2** (sin JSX ni compilador, con `createElement`) servida de cinco formas. Para cada una se mide lo que recibiría un bot que no ejecuta JavaScript: `h1`, `title` y `canonical` en el `<head>`, enlaces, y las reseñas, que llegan de una API lenta dentro de un `<Suspense>`.

## Ficheros

| Fichero | Qué es |
|---|---|
| [`app.mjs`](app.mjs) | El componente `Producto`: `<title>`, `<meta>` y `<link rel="canonical">` **dentro del componente** (React 19 los sube al `<head>`), un `<a href>`, un `<button>` que no es enlace y las reseñas en `<Suspense>` |
| [`prueba.mjs`](prueba.mjs) | Genera las cinco variantes y comprueba qué hay en cada HTML |
| [`package.json`](package.json) | `react` y `react-dom` 19.2.0, versiones fijas |

## Cómo probarlo

Git Bash, con Node.js (probado con 24.12):

```bash
cd ejercicios/react-seo
npm install          # solo la primera vez: react y react-dom en node_modules/ (fuera de git)
node prueba.mjs
```

Salida real (2026-10-25):

```
│ variante               │ h1 │ title en head │ canonical en head │ enlaces <a href> │ reseñas │ fallback "Cargando" │
│ 'CSR (SPA)'            │ 0  │ false         │ false             │ 0                │ false   │ false               │
│ 'renderToString'       │ 1  │ true          │ true              │ 1                │ false   │ true                │
│ 'stream, onShellReady' │ 1  │ true          │ true              │ 1                │ false   │ true                │
│ 'stream, onAllReady'   │ 1  │ true          │ true              │ 1                │ true    │ false               │
│ 'prerender (SSG)'      │ 1  │ true          │ true              │ 1                │ true    │ false               │

6/6 comprobaciones OK
```

## Qué enseña

- **CSR (lo que da Vite por defecto):** el bot recibe `<div id="root"></div>`. Cero `h1`, cero enlaces; el `title` es el genérico del `index.html`.
- **React 19 sube el `<title>`, la `<meta>` y el `canonical` al `<head>`** aunque estén escritos dentro del componente, también al renderizar en servidor.
- **`renderToString` no espera a los datos.** Las reseñas salen como «Cargando reseñas...». React lo documenta: *«If a component suspends, renderToString immediately sends its fallback as HTML.»*
- **Con streaming, los primeros bytes llevan el *fallback*.** El contenido real llega después en la misma respuesta, con un `<script>` que lo coloca. Cortando en `onShellReady` no hay reseñas; en `onAllReady` (*«for crawlers and static generation»*, según React), sí.
- **`prerender` (SSG) espera a todo:** el HTML final, con reseñas, `title` y `canonical`.
- **El `<button>` no cuenta como enlace.** Solo el `<a href>` aparece en el recuento.
