# Laravel y SEO: Laravel Head, redirecciones y la barra final

Ejercicio de **Frameworks y Tecnologías, clase 12 Laravel**.

Un Laravel 13 real con **Laravel Head** (`laravel/head`, el paquete oficial para el `<head>`), una página de producto, dos redirecciones, una zona `noindex` y un 404 propio. Se mide lo que devuelve cada URL.

## Ficheros

| Fichero | Qué es |
|---|---|
| [`routes/web.php`](routes/web.php) | Las rutas: `/producto` con `Head::title()->description()->canonical()`, `Route::redirect`, `Route::permanentRedirect` y `/admin` con `withHead(robots: ...)` |
| [`resources/views/layout.blade.php`](resources/views/layout.blade.php) | Layout con `@head` (Laravel Head) y `@stack('head')` |
| [`resources/views/producto.blade.php`](resources/views/producto.blade.php) | La página; empuja su JSON-LD al `<head>` con `@push` |
| [`resources/views/errors/404.blade.php`](resources/views/errors/404.blade.php) | 404 propio con enlace de vuelta |
| [`prueba.php`](prueba.php) | Instala Laravel 13 + `laravel/head` en la carpeta temporal (si no está), copia estos ficheros, sirve con el router de Laravel y hace 8 comprobaciones |

El proyecto se instala en `%TEMP%\laravel-seo`, **fuera de OneDrive** (`vendor/` pesa decenas de MB). En el repo solo están los ficheros propios.

## Cómo probarlo

Git Bash, con el **PHP 8.3** de Laragon (Laravel 13 pide PHP 8.3+, y la base de datos por defecto del esqueleto es SQLite: el PHP 8.5 de Laragon no trae `pdo_sqlite`):

```bash
cd ejercicios/laravel-seo
php83=$(ls -d /c/laragon/bin/php/php-8.3* | tail -1)/php.exe
"$php83" prueba.php      # la primera vez instala Laravel con Composer (unos 5 min)
```

Salida real (2026-10-25, Laravel 13.38.0, laravel/head 0.2.2):

```
Ruta                     Cód. title / Location               canonical                          robots
/producto                200   Zapatillas de trail | Tienda   https://127.0.0.1:8100/producto
/producto/               200   Zapatillas de trail | Tienda   https://127.0.0.1:8100/producto
/producto?utm_source=x   200   Zapatillas de trail | Tienda   https://127.0.0.1:8100/producto
/viejo                   302   /producto
/antiguo                 301   /producto
/admin                   200                                                                     noindex, nofollow
/no-existe               404

public/.htaccess trae la regla «Redirect Trailing Slashes»: sí (solo Apache/LiteSpeed)
Laravel 13.38.0

8/8 comprobaciones OK
```

## Qué enseña

- **`Route::redirect` es un 302.** Para una migración hay que usar `Route::permanentRedirect` o pasar `301` como tercer argumento.
- **Laravel sirve `/producto/` igual que `/producto`.** La barra final solo se redirige con 301 si el servidor lee el `.htaccess` del esqueleto (Apache, LiteSpeed). Con nginx o `php artisan serve` las dos URLs dan 200; el `canonical` las une.
- **`Head::canonical()` quita los parámetros** (`?utm_source=x`) y **fuerza `https`**, aunque la petición sea `http`: aquí se ve con el servidor local.
- **`withHead(robots: ...)` declara el `noindex` en la ruta**, sin tocar la vista.
- **El 404 es real** y usa la vista `errors/404.blade.php`.
- **`@push('head')`** permite que cada vista meta su JSON-LD en el `<head>` del layout.
