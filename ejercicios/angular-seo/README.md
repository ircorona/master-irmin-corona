# Angular y SEO: los tres modos de renderizado, medidos

Ejercicio de **Frameworks y Tecnologías, clase 11 Angular**.

Una página de producto en Angular con SSR (`@angular/ssr`), servida en los tres modos que define angular.dev: **Prerender** (en el build), **Server** (en cada petición) y **Client** (en el navegador), más una ruta comodín con **404**. Se mide el HTML que recibe un bot sin JS.

## Ficheros

| Fichero | Qué es |
|---|---|
| [`app/producto.ts`](app/producto.ts) | El componente: `h1`, description con el servicio `Meta`, `canonical` sobre el `DOCUMENT`, un `<a routerLink>` y un `<button routerLink>` |
| [`app/app.routes.ts`](app/app.routes.ts) | Rutas con `title` (Angular pone el `<title>` solo) |
| [`app/app.routes.server.ts`](app/app.routes.server.ts) | Un `RenderMode` por ruta y `status: 404` en el comodín |
| [`app/app.html`](app/app.html) | Solo `<router-outlet />` |
| [`prueba.mjs`](prueba.mjs) | Crea el proyecto (si no existe), copia `app/`, compila, arranca el servidor de Angular dos veces (sin y con `NG_ALLOWED_HOSTS`) y comprueba cada ruta |

El proyecto se genera en la carpeta temporal del sistema (`%TEMP%\ng-seo\tienda`), **fuera de OneDrive**: su `node_modules` pesa cientos de MB. En el repo solo están los ficheros propios.

**Angular 21, no 22.** El CLI de Angular 22 exige Node 24.15 o superior; este equipo tiene 24.12 y no se ha actualizado. Se usa `@angular/cli@21.2.28` (Angular 21.2.25), que admite Node 24.0+.

## Cómo probarlo

Git Bash, con Node.js 24:

```bash
cd ejercicios/angular-seo
node prueba.mjs      # la primera vez crea el proyecto (1-2 min); luego compila y prueba
```

Salida real (2026-10-25):

```
Sin NG_ALLOWED_HOSTS:
│ ruta         │ codigo │ h1 │ title    │ description │ canonical │ a href │
│ '/prerender' │ 200    │ 0  │ 'Tienda' │ false       │ false     │ 0      │
│ '/servidor'  │ 200    │ 0  │ 'Tienda' │ false       │ false     │ 0      │
│ '/cliente'   │ 200    │ 0  │ 'Tienda' │ false       │ false     │ 0      │
│ '/no-existe' │ 200    │ 0  │ 'Tienda' │ false       │ false     │ 0      │
Log del servidor: Header "host" ... is not allowed. Falling back to client side rendering.

Con NG_ALLOWED_HOSTS=localhost:
│ ruta         │ codigo │ h1 │ title                          │ description │ canonical │ a href │
│ '/prerender' │ 200    │ 1  │ 'Zapatillas de trail | Tienda' │ true        │ true      │ 1      │
│ '/servidor'  │ 200    │ 1  │ 'Zapatillas de trail | Tienda' │ true        │ true      │ 1      │
│ '/cliente'   │ 200    │ 0  │ 'Tienda'                       │ false       │ false     │ 0      │
│ '/no-existe' │ 404    │ 1  │ 'No encontrada'                │ false       │ false     │ 0      │

6/6 comprobaciones OK
```

## Qué enseña

- **Sin `allowedHosts`, Angular 21 sirve CSR a todo el mundo con un 200.** El servidor rechaza el `Host` que no conoce, cae a renderizado en cliente y no avisa más que en su log. Hasta la ruta prerenderizada, cuyo HTML completo está en disco, sale vacía. Es la trampa más peligrosa del módulo: la web «funciona» y Googlebot recibe una cáscara.
- **Prerender y Server entregan todo**: `h1`, `title` de la ruta, description del servicio `Meta`, `canonical` y el enlace.
- **Client es lo que Angular hace por defecto sin SSR**: cero contenido.
- **`routerLink` solo genera `href` en un `<a>`.** En el `<button>` navega, pero no es un enlace.
- **`status: 404` en la ruta del servidor da un 404 real**, en vez del soft 404 de una SPA.
