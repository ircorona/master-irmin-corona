# Qué ocultamos en móvil, y cómo

Ejercicio de **Frameworks y Tecnologías, clase 02 El SEO y la programación**.

En clase: «no es lo mismo ocultar una navbar porque la tienes para móvil y para escritorio [...] que una imagen no se muestra». Este ejercicio mide qué pide de verdad el navegador cuando algo se oculta con CSS, y qué queda en el HTML que lee Google.

## Ficheros

| Fichero | Qué hace |
|---|---|
| [`index.html`](index.html) | Dos menús (uno por breakpoint) y cinco imágenes ocultadas de formas distintas |
| [`router.php`](router.php) | Router de `php -S`: apunta cada petición en `peticiones.log` y sirve un PNG de 1×1 para cualquier `/img/*.png` |
| [`prueba.php`](prueba.php) | Arranca el servidor, carga la página con Chrome headless a 390 px y a 1280 px, y dice qué imágenes se pidieron en cada caso. Sale con 1 si algo no es lo esperado |

## Cómo probarlo

Git Bash, desde la raíz del repo (Chrome instalado):

```bash
cd ejercicios/ocultar-en-movil
php=/c/laragon/bin/php/php-8.5.7-nts-Win32-vs17-x64/php.exe
"$php" prueba.php
```

Salida real (2026-10-25):

```
Imagen                       Movil    Escritorio
A <img> display:none         pedida   pedida     OK
B <img> display:none lazy    pedida   pedida     OK
C <picture> movil            pedida   -          OK
C <picture> escritorio       -        pedida     OK
D fondo CSS escritorio       -        pedida     OK
E control lazy a 9000px      pedida   pedida     OK

Enlaces en el HTML: 6 (3 URLs distintas)
6/6 casos con el resultado esperado
```

## Comprobar B en un navegador real

Chrome headless **ignora `loading="lazy"`**: la imagen E, visible pero 9000 px por debajo, también se pide. Por eso B sale «pedida» en la prueba, y así está anotado como lo esperado. Para ver el comportamiento real:

1. `"$php" -S 127.0.0.1:8093 router.php` y abrir `http://127.0.0.1:8093/index.html` en Chrome.
2. DevTools → *Toggle device toolbar* (Ctrl+Shift+M) a 390 px, pestaña Network con *Disable cache*, recargar.
3. Según [web.dev](https://web.dev/articles/browser-level-image-lazy-loading), Chrome, Safari y Firefox **no cargan** imágenes `lazy` con `display: none`, ni la E hasta acercarse con el scroll.

## Qué enseña

- **`display:none` en un `<img>` no ahorra nada.** A se descarga igual en móvil: ocultar no es no cargar.
- **Para elegir imagen por pantalla, `<picture>` o `srcset`.** C pide solo la que toca a cada ancho.
- **Un fondo CSS dentro de una media query solo se pide si aplica.** D no se descarga en móvil. Pero un fondo no es contenido para Google: sin `alt` y sin indexarse como imagen de la página.
- **Dos menús = enlaces duplicados en el HTML.** 6 enlaces, 3 URLs. Google ve los dos (el HTML no cambia con el ancho), así que no es un problema de rastreo, sino de peso y de enlaces repetidos. Un solo menú adaptado con CSS es más limpio.
- **Ocultar con CSS no es quitar del HTML.** Lo que el usuario de móvil no ve sigue en el HTML, que es lo que indexa Google con *mobile-first*. Lo que importa es el contrario: que el móvil no **pierda** contenido que sí tiene el escritorio.
