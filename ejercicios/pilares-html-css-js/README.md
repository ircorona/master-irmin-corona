# Los tres pilares: dónde vive cada texto

Ejercicio de **Frameworks y Tecnologías, clase 07 Pilares webs**.

HTML es el esqueleto, CSS la presentación y JS el comportamiento. Esta página pone el mismo tipo de dato (un texto) en capas distintas y mide en cuál aparece: en el HTML que manda el servidor, en el DOM tras ejecutar el JS, y en el `innerText` del navegador.

## Cómo probarlo

Git Bash, desde la raíz del repo (Chrome instalado):

```bash
cd ejercicios/pilares-html-css-js
php=/c/laragon/bin/php/php-8.5.7-nts-Win32-vs17-x64/php.exe
"$php" prueba.php
```

Salida real (2026-10-25):

```
Texto                      HTML   DOM    innerText  En pantalla
<h1>                       x      x      x          sí
<span> con aspecto de h1   x      x      x          sí, como un h1
display:none               x      x      -          no
::before { content }       -      -      -          sí
alt de <img>               x      x      -          no (solo si falla)
insertado por JS           -      x      x          sí

<h1> en el HTML: 1 (el span grande no cuenta)
<img> en el HTML: 1 (el fondo CSS no es una imagen de la página)

7/7 comprobaciones OK
```

Las columnas HTML, DOM e `innerText` están medidas (`php -S` + Chrome headless). «En pantalla» no se mide: es lo que pinta el navegador.

## Qué enseña

- **El HTML manda.** El `<span>` de 32 px parece un título, pero para el HTML es un `span`: hay un solo `<h1>`.
- **`display:none` oculta, no borra.** El texto sigue en el HTML y en el DOM; el navegador solo deja de considerarlo texto visible (`innerText`).
- **El texto generado con CSS no existe en el documento.** `::before { content }` se pinta en pantalla pero no está ni en el HTML ni en el DOM.
- **Una imagen de fondo CSS no es una imagen de la página.** Ni `<img>` ni `alt`: Google no indexa imágenes CSS.
- **El texto insertado por JS solo existe tras ejecutarlo**: en el DOM sí, en el HTML no.
