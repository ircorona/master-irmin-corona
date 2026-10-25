# Fundamentos web: tu primera página en PHP

Ejercicio de **Frameworks y Tecnologías, clase 03 Repaso fundamentos web**.

En clase Carlos escribió en directo un `index.php`: una variable, un array de posts y HTML con `echo` en medio. Aquí está el código de la foto, la misma página con lo que le falta para SEO y una prueba que mira lo que llega de verdad al navegador.

## Ficheros

| Fichero | Qué es |
|---|---|
| [`clase.php`](clase.php) | El código de clase. Líneas 1-27 copiadas de la foto, incluido el `'Post 4'` duplicado que Carlos estaba tecleando. **El segundo `<div>` sale cortado en la foto: el `foreach` es una reconstrucción** |
| [`mejorado.php`](mejorado.php) | La misma página con `lang`, `meta charset`, `viewport`, `description`, `canonical`, escapado con `htmlspecialchars` y los posts como enlaces |
| [`prueba.php`](prueba.php) | Sirve las dos con `php -S` en el puerto 8095 y hace 8 comprobaciones sobre el HTML recibido |

## Cómo probarlo

Git Bash, desde la raíz del repo:

```bash
cd ejercicios/php-fundamentos
php=/c/laragon/bin/php/php-8.5.7-nts-Win32-vs17-x64/php.exe
"$php" prueba.php
```

Salida real (2026-10-25, PHP 8.5.7):

```
clase: el navegador no recibe código PHP          OK
clase: charset solo por cabecera HTTP              OK
clase: hay espacio en blanco antes del DOCTYPE     OK
clase: 6 posts, "Post 4" repetido                  OK
clase: 0 enlaces a los posts                       OK
mejorado: empieza por el DOCTYPE                   OK
mejorado: lang, charset y viewport                 OK
mejorado: 5 enlaces <a href> a los posts           OK

8/8 comprobaciones OK
```

Para ver el HTML tal cual lo recibe el navegador:

```bash
"$php" -S 127.0.0.1:8095 &
curl -s -D - http://127.0.0.1:8095/clase.php
kill %1
```

## Qué enseña

- **PHP se ejecuta en el servidor; el navegador solo recibe HTML.** Ni `<?php`, ni `$test`, ni el array llegan: llega el resultado. Es el «HTML del servidor» del módulo de Renderización.
- **Los acentos de `clase.php` se ven bien por suerte.** La página no tiene `<meta charset>`; funciona porque PHP manda `Content-Type: text/html; charset=UTF-8` por defecto. Abierto como fichero, o en un servidor sin esa cabecera, «página» puede salir como `pÃ¡gina`.
- **El salto de línea tras `?>` acaba en el HTML.** Por eso `clase.php` empieza con una línea en blanco antes del `<!DOCTYPE>`. Los navegadores lo toleran, pero en un fichero que haga `header()` después, ese espacio ya enviado provoca *headers already sent*.
- **Un listado de posts sin enlaces no enlaza nada.** Los `<p>` de clase son texto; para Google, el blog se descubre por los `<a href>` de `mejorado.php`.
- **`echo` sin escapar es un riesgo cuando el dato viene de fuera.** Con un literal no pasa nada; con un título de base de datos o un parámetro de URL, `htmlspecialchars` evita romper el HTML (o una inyección).
