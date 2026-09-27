# Ejercicio — Una página, dos idiomas según la URL

**Módulo:** SEO internacional · **Enunciado:** *«Genera en tu web de código básica una página que pueda tener 2 idiomas dependiendo de la URL.»*

## Estructura

```
ejercicios/idiomas-por-url/
├── index.php                 → $lang = 'es'   URL: /ejercicios/idiomas-por-url/
├── en/index.php              → $lang = 'en'   URL: /ejercicios/idiomas-por-url/en/
├── languages/plantilla.php   → la plantilla compartida (no se sirve sola)
├── sitemap-lang.xml          → el mismo hreflang, declarado desde el sitemap
└── valida-hreflang.py        → comprueba las cinco reglas del módulo
```

**Es una estructura de subfolders**, que es justo la que recomienda el módulo: un solo dominio, toda la autoridad junta, y el español como versión por defecto en la raíz.

El mecanismo es el de la clase, en tres pasos:

1. Cada URL es un fichero PHP de **dos líneas**: fija `$lang` y llama a la plantilla.
2. La plantilla lee `$lang` y elige el bloque de textos.
3. El mismo HTML sale en un idioma o en otro, con su `lang`, su `title`, su `canonical` y su `hreflang`.

## Cómo probarlo

Con Laragon levantado:

- Español → <http://master-irmin-corona.test/ejercicios/idiomas-por-url/>
- Inglés → <http://master-irmin-corona.test/ejercicios/idiomas-por-url/en/>

Si lo sirves desde otro dominio, cambia **una sola línea** — `$base`, al principio de `languages/plantilla.php`.

Sin navegador, desde la línea de comandos:

```bash
PHP="/c/laragon/bin/php/php-8.5.7-nts-Win32-vs17-x64/php.exe"

"$PHP" -l languages/plantilla.php      # comprobar sintaxis
"$PHP" index.php                       # ver el HTML de la versión española
"$PHP" en/index.php                    # ver el HTML de la versión inglesa
```

## Comprobado (2026-08-30, PHP 8.5.7)

| Comprobación | Resultado |
|---|---|
| Sintaxis de los tres ficheros | ✅ `No syntax errors detected` |
| `<html lang>` cambia con la URL | ✅ `es` en la raíz, `en` en `/en/` |
| `<title>` y `<h1>` traducidos | ✅ «Ejemplos de hreflang» / «hreflang examples» |
| **Los dos bloques `hreflang` son idénticos** | ✅ `diff` sin diferencias → **recíproco y autorreferencial** |
| **El `canonical` es distinto en cada versión** | ✅ cada una apunta a sí misma |
| `x-default` | ✅ apunta al español (la versión por defecto) |
| Acceso directo a `languages/plantilla.php` | ✅ devuelve **404**, no un HTML a medias |

## Validación automática

```bash
cd ejercicios/idiomas-por-url
python valida-hreflang.py                       # usa el PHP de Laragon
python valida-hreflang.py "ruta/a/php.exe"      # o el que le pases
```

Renderiza las dos páginas y comprueba autorreferencia, `x-default`, forma ISO del `lang`,
reciprocidad, bloque `hreflang` idéntico y canonical propio. Devuelve **0** si todo está bien
y **1** si algo falla, así que sirve tal cual en un hook o en un CI.

Última ejecución (2026-08-30): **14 comprobaciones, todas OK.**

## El sitemap

[`sitemap-lang.xml`](sitemap-lang.xml) declara lo mismo por la otra vía. Comprobado con un
parser XML: bien formado, namespace `xhtml` declarado, bloques idénticos, cada `<url>`
incluyéndose a sí misma, `x-default` presente, y las URLs declaradas coinciden con las `<loc>`.

> ⚠️ El ejercicio declara el `hreflang` **por las dos vías a la vez** (HTML y sitemap) a
> propósito, para tenerlas las dos practicadas. **En producción se elige una:** no es un
> error tener ambas, pero son dos sitios que mantener y dos donde desincronizarse.

Para que el sitemap sirva de algo, falta declararlo en el `robots.txt`:

```
Sitemap: http://master-irmin-corona.test/ejercicios/idiomas-por-url/sitemap-lang.xml
```

El `diff` de los bloques `hreflang` es la prueba que importa: **las dos páginas emiten exactamente las mismas etiquetas**. Eso es lo que significa «bidireccional», y es lo que falla en el ejemplo de *The Independent* de la clase 03.

## Diferencias respecto al código de clase, y por qué

El código de la pizarra funciona. Estas son las cinco cosas que cambié, todas con motivo:

| # | En clase | Aquí | Por qué |
|---|---|---|---|
| 1 | `include $_SERVER['DOCUMENT_ROOT'].'/languages/prueba.php'` | `require __DIR__ . '/languages/plantilla.php'` | `DOCUMENT_ROOT` depende de cómo esté montado el servidor y **se rompe si el proyecto no está en la raíz** — que es exactamente este caso, al vivir dentro de `ejercicios/`. `__DIR__` es relativo al fichero y siempre acierta. Y `require` falla ruidosamente: con `include`, si la plantilla no aparece sale una página en blanco |
| 2 | `<html Lang="...">` | `<html lang="...">` | Funciona igual (los atributos HTML no distinguen mayúsculas), pero la convención es minúscula y así no chirría en las revisiones |
| 3 | *(no aparece)* | `<!DOCTYPE html>` | Sin él, el navegador entra en *quirks mode* |
| 4 | *(no aparece)* | `<meta charset="utf-8">` | **El más importante de la lista.** Los textos llevan acentos («Ejemplos», «página», «Español»). Sin declarar el charset, el navegador adivina y salen los caracteres rotos |
| 5 | `if ($lang == 'es') {…} else {…}` con variables sueltas | Un array `$textos['es']` / `$textos['en']` | Con dos idiomas da igual; con cinco, el `if/else` se convierte en una escalera y es fácil olvidar una variable en una rama. Añadir un idioma aquí es **añadir una clave** |

Y tres cosas que añadí porque son el objetivo SEO del ejercicio y en las capturas de clase no llegaron a salir:

- **`hreflang` recíproco + autorreferencial + `x-default`**, generado en un bucle a partir de `$urls`, así que **es imposible que las dos versiones se desincronicen**.
- **`canonical` autorreferencial** en cada versión — la regla de la clase 03: *el canonical siempre apunta a la versión que es*.
- **Guarda de acceso directo:** si alguien pide `languages/plantilla.php` sin pasar por una de las dos entradas, responde 404 en vez de servir un HTML sin idioma.

## Detalle que confunde: los dos `hreflang`

En el HTML aparecen dos cosas que se llaman igual y no son lo mismo:

```html
<!-- ESTO es lo que Google lee como versión alternativa -->
<link rel="alternate" hreflang="en" href="…/en/">

<!-- ESTO es solo una pista para el usuario y la accesibilidad -->
<a href="…/en/" hreflang="en">Read this page in English</a>
```

El `hreflang` de un `<a>` es HTML válido y está bien puesto, pero **no declara una versión alternativa**. Para eso solo cuentan los `<link>` del `<head>`.

## Lo que faltaría en un sitio real

El ejercicio se queda aquí a propósito, pero para producción hay tres cosas más:

1. **Traducir también el slug** (`/es/contacto` frente a `/en/contact`), como hacen BlaBlaCar y GoldenRace en la clase 03.
2. **Apuntar el sitemap desde el `robots.txt`** — el sitemap ya está (`sitemap-lang.xml`), falta la línea `Sitemap:`.
3. **Traducir el `alt`** de las imágenes que se quieran posicionar.

## Referencias

Las notas viven en `knowledge/seo-internacional/`, que está en el `.gitignore`:

- `03-versiones-localizadas.md` — las reglas del `hreflang`.
- `07-una-pagina-distintos-idiomas.md` — la clase de la que sale este ejercicio.
- `08-meta-hreflang.md` — la chuleta de las cuatro formas y el validador.
- `09-sitemaps.md` — el `sitemap-lang.xml`.
