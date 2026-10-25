# JavaScript y SEO: enlaces, router y parches

Ejercicio de **Frameworks y Tecnologías, clase 04 JavaScript & SEO**.

JavaScript da buena experiencia de usuario: navegar sin recargar, insertar contenido, corregir el HTML desde el navegador. Este ejercicio mide qué parte de eso ve un bot que no ejecuta JS (el HTML de `curl`) y qué parte ve Google tras renderizar (el DOM de Chrome headless).

## Ficheros

| Fichero | Qué hace |
|---|---|
| [`index.html`](index.html) | Cinco formas de enlazar (A-E), un router con History API, y dos técnicas del artículo de Carlos «JS para mejorar tu contenido» copiadas tal cual |
| [`router.php`](router.php) | Router de `php -S`: devuelve la página en las rutas que existen y **404 en las demás** (sin soft 404) |
| [`prueba.php`](prueba.php) | Compara HTML y DOM para cada enlace, comprueba las técnicas de Carlos y los códigos de respuesta. Sale con 1 si algo no es lo esperado |

## Cómo probarlo

Git Bash, desde la raíz del repo (Chrome instalado):

```bash
cd ejercicios/javascript-seo
php=/c/laragon/bin/php/php-8.5.7-nts-Win32-vs17-x64/php.exe
"$php" prueba.php
```

Salida real (2026-10-25):

```
   Enlace                      HTML (curl)   DOM (Chrome)  Google (documentado)
A  <a href> + History API      enlace        enlace        sigue /productos/
B  <a onclick> sin href        sin href      sin href      no sigue nada
C  <span data-href>            sin href      sin href      no sigue nada
D  <a href="#/blog">           href=#/blog   href=#/blog   ignora el #: misma URL
E  <a href> insertado por JS   ausente       enlace        sigue tras renderizar

Carlos, técnica 1 (anchor text): "Este es el nuevo Anchor Text"
Carlos, técnica 5 (nofollow -> noopener): rel final = "nofollow"  (no cambió: newLink no existe, ReferenceError)

Códigos: /productos/ = 200, /no-existe/ = 404

7/7 comprobaciones OK
```

Para navegarlo a mano: `"$php" -S 127.0.0.1:8096 router.php` y abrir `http://127.0.0.1:8096/`. Al pulsar «Productos» la URL cambia sin recargar; al recargar en `/productos/` el servidor responde 200; en `/lo-que-sea/`, 404.

**Lo medido y lo documentado.** Las columnas HTML y DOM son mediciones. La columna «Google» aplica la [documentación de enlaces rastreables](https://developers.google.com/search/docs/crawling-indexing/links-crawlable) de Google, no el comportamiento observado de Googlebot.

## Qué enseña

- **Solo A es un enlace en los dos lados.** `<a href>` real en el HTML y navegación instantánea con `pushState` para el usuario: la buena experiencia sin coste para el SEO.
- **B y C funcionan para el usuario y no existen para Google.** Un `onclick` o un `data-href` no son enlaces.
- **D es una sola URL.** Todo lo que va detrás de `#` no se envía al servidor y Google no lo trata como página distinta.
- **E solo existe para quien ejecuta JS.** Google lo sigue tras renderizar; GPTBot o ClaudeBot, que no ejecutan JS, no.
- **El parche de Carlos funciona a medias.** La técnica 1 cambia el anchor text en el DOM. La técnica 5, tal como está publicada, usa `newLink`, que no existe: lanza un `ReferenceError` y el `rel` sigue en `nofollow`. Corregida sería `if (link.rel === 'nofollow') { link.rel = 'noopener'; }`.
- **Una SPA necesita un servidor que distinga rutas.** Devolver la página en todas las URLs evita el 404 al recargar, pero convierte cada URL inventada en un soft 404. `router.php` solo responde 200 en las rutas que existen.
