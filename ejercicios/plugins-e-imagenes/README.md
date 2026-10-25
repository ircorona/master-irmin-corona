# Imágenes, plugins propios y un solo plugin de SEO

Ejercicio de **Frameworks y Tecnologías, clase 05 PHP y servidor**.

Los tres puntos de la clase, medidos: optimizar imágenes en el servidor con PHP, construir un plugin propio, y usar un solo plugin de SEO (o ninguno). Todo corre con el **PHP 8.3 de Laragon**, que trae GD con WebP y AVIF y `pdo_sqlite`. El 8.5 de Laragon no tiene GD activado.

## Ficheros

| Fichero | Qué es |
|---|---|
| [`optimizar.php`](optimizar.php) | Script suelto: redimensiona una imagen a 400, 800 y su ancho original, la guarda en JPEG, WebP y AVIF, y genera el `<picture>` con `srcset` |
| [`mi-seo/mi-seo.php`](mi-seo/mi-seo.php) | Plugin de WordPress propio: `meta description` y JSON-LD `Article` en las entradas, y **se apaga solo si hay Yoast o Rank Math** |
| [`formato-webp.php`](formato-webp.php) | mu-plugin: las imágenes subidas se guardan en WebP (filtro `image_editor_output_format`) a calidad 75 (`wp_editor_set_quality`) |
| [`wp.php`](wp.php) | Acciones dentro de WordPress: instalar, activar plugins, subir una imagen |
| [`prueba.php`](prueba.php) | Monta un WordPress con SQLite en una carpeta temporal, prueba seis combinaciones de plugins de SEO y sube la imagen sin y con el mu-plugin. Sale con 1 si algo falla |

La primera vez `prueba.php` descarga WordPress, SQLite Database Integration, Yoast y Rank Math a `.cache/` (fuera de git; reutiliza los zips de `../indirecciones/.cache/` si existen). No toca el Apache ni el MySQL de Laragon.

## Cómo probarlo

Git Bash, desde la raíz del repo:

```bash
cd ejercicios/plugins-e-imagenes
php83=$(ls -d /c/laragon/bin/php/php-8.3* | tail -1)/php.exe
"$php83" optimizar.php     # unos 4 s
"$php83" prueba.php        # 1-2 min: instala WordPress y lo prueba
```

## Salida real (2026-10-25)

`optimizar.php` con `images/how_do_you_turn_this_on.jpg` (1024×623, 160,3 KB):

```
Ancho    JPEG q82   WebP q80   AVIF q50
400        35.8 KB     32.1 KB     20.7 KB
800       108.6 KB     92.0 KB     51.1 KB
1024      167.6 KB    142.4 KB     82.1 KB

Mismo ancho que el original: WebP 11 % y AVIF 49 % menos que el JPEG original
```

`prueba.php` (WordPress 7.1.3, Yoast 28.8-RC6 según su cabecera, Rank Math 1.0.280):

```
Plugins                 title canonical    descr.    robots  og:title   JSON-LD  KB HTML
Ningún plugin              1         1         0         1         0         0     65.4
Mi SEO                      1         1         1         1         0         1     65.8
Yoast                       1         1         0         1         1         1     67.8
Rank Math                   1         1         1         1         1         1     68.2
Yoast + Rank Math           1         2         1         1         2         2     70.7
Mi SEO + Yoast              1         1         0         1         1         1     67.8

Subida con el mu-plugin formato-webp.php:
  how_do_you_turn_this_on-1.webp     121.1 KB (subida)
  how_do_you_turn_this_on-1-300x183.webp 13.4 KB  [medium]
  how_do_you_turn_this_on-1-150x150.webp 5.7 KB  [thumbnail]
  how_do_you_turn_this_on-1-768x467.webp 71.8 KB  [medium_large]

8/8 comprobaciones OK
```

## Qué enseña

- **Volver a comprimir un JPEG puede engordarlo.** A su mismo ancho, el JPEG q82 pesa 167,6 KB frente a los 160,3 KB del original, que ya venía comprimido. Lo que de verdad ahorra es **redimensionar** (400 px: 35,8 KB) y **cambiar de formato** (AVIF: −49 %).
- **WebP no siempre gana por goleada.** En esta imagen ahorra un 11 %; AVIF casi la mitad.
- **WordPress convierte a WebP con un filtro, pero a calidad 86.** Sin `wp_editor_set_quality`, la misma subida dio **174,9 KB en WebP frente a 160,3 KB en JPEG**: más pesada. A calidad 75, 121,1 KB.
- **WordPress ya pone el `canonical` y una `meta robots` (`max-image-preview:large`) sin plugins.** Un plugin propio no debe repetirlos.
- **Dos plugins de SEO = etiquetas duplicadas.** Yoast + Rank Math: 2 `canonical`, 2 `og:title`, 2 JSON-LD. Cada uno solo, ninguna.
- **Un plugin propio puede convivir si se aparta.** `mi-seo` comprueba `WPSEO_VERSION` y `RankMath` y no imprime nada: «Mi SEO + Yoast» es idéntico a «Yoast».
- **Rank Math no hace nada en el front-end hasta que conectas una cuenta suya o saltas el asistente** (opción `rank_math_registration_skip`). Activado sin más, la página sale igual que sin plugins. `wp.php` simula el «saltar».
