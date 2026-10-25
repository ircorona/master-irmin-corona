# Librería vs framework, y SSR vs SSG vs ISR

Ejercicio de **Frameworks y Tecnologías, clase 01 Introducción**.

En clase: la librería te da piezas hechas (FPDF para PDFs); el framework además te impone **su método** (Laravel con Blade, Symfony con Twig, Shopify con Liquid). Y al final, la estrategia de render que Carlos llama «la revolución»: **ISR**. Tres scripts de PHP de consola, sin dependencias, para ver las tres ideas funcionando.

## Ficheros

| Fichero | Qué enseña |
|---|---|
| [`libreria.php`](libreria.php) | Una clase `Slug` que convierte títulos en URLs. **Tu código** decide cuándo llamarla: el flujo es tuyo |
| [`framework.php`](framework.php) | Un mini framework: registras rutas y controladores, y **él** llama a tu código (inversión de control). Trae motor de plantillas al estilo Blade/Twig (`{{ x }}` escapa, `{!! x !!}` no) y devuelve **404 de verdad** en rutas que no existen |
| [`isr.php`](isr.php) | SSR, SSG e ISR respondiendo a las mismas peticiones con un reloj simulado. El precio cambia en t=10; ISR tiene `revalidate = 60` |

## Cómo probarlo

```bash
cd ejercicios/frameworks-libreria-vs-framework
php libreria.php
php framework.php
php isr.php
```

Usa el PHP de Laragon (probado con 8.5.7). No necesita `mbstring`.

## Salida real (2026-10-25)

```
$ php libreria.php
/blog/que-es-un-framework     <- ¿Qué es un framework?
/blog/libreria-vs-framework   <- Librería vs framework
/blog/isr-la-revolucion       <- ISR: la revolución

$ php framework.php
HTTP 200  /
<title>Inicio</title><h1>Inicio</h1>

HTTP 200  /blog
<h1>Blog</h1><b>hola</b><p>Escapado: &lt;b&gt;hola&lt;/b&gt;</p>

HTTP 404  /no-existe
<h1>No encontrada</h1>

$ php isr.php
SSR (render en cada petición)
  t=  0  precio 100 EUR render=sí
  t= 30  precio 80 EUR render=sí
  t= 61  precio 80 EUR render=sí
  t= 62  precio 80 EUR render=sí
  t=200  precio 80 EUR render=sí

SSG (build en t=0, sin redeploy)
  t=  0  precio 100 EUR render=no
  t= 30  precio 100 EUR render=no
  t= 61  precio 100 EUR render=no
  t= 62  precio 100 EUR render=no
  t=200  precio 100 EUR render=no

ISR (revalidate=60)
  t=  0  precio 100 EUR x-nextjs-cache: HIT
  t= 30  precio 100 EUR x-nextjs-cache: HIT
  t= 61  precio 100 EUR x-nextjs-cache: STALE
  t= 62  precio 80 EUR x-nextjs-cache: HIT
  t=200  precio 80 EUR x-nextjs-cache: STALE
```

## Qué enseña

- **Librería = tú llamas. Framework = te llaman.** En `libreria.php` el `foreach` es tuyo. En `framework.php` solo escribes los huecos (`$app->get(...)`) y `run()` decide el resto.
- **El motor de plantillas escapa por defecto.** `{{ cuerpo }}` sale como `&lt;b&gt;`; para meter HTML hay que pedirlo (`{!! !!}` en Blade, `|raw` en Twig).
- **El 404 lo decide el framework.** Aquí devuelve 404; una SPA que pinta «No encontrada» con código 200 es un soft 404.
- **SSG se queda viejo, SSR cuesta servidor, ISR está en medio.** Entre t=10 y t=61 ISR sigue sirviendo el precio viejo: es el precio de la velocidad. La primera visita después de caducar (t=61) recibe la copia caducada (`STALE`) y dispara la regeneración; la siguiente (t=62) ya ve lo nuevo. Es el comportamiento que documenta Next.js, con su cabecera `x-nextjs-cache`.
- **Simulado, no medido.** `isr.php` reproduce las reglas de la documentación de Next.js, no ejecuta Next.js. Para verlo de verdad: `next build && next start` y mirar la cabecera `x-nextjs-cache` con `curl -I`.
