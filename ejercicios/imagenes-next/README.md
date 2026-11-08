# Imágenes en Next.js: qué URL ve Google

Ejercicio de **Migraciones, clase 12 Desarrollo – Metodología** (fila 27 de la plantilla: «Ojo con urls que crean los frameworks de JS»).

La misma imagen servida con `next/image` de cuatro formas, en **Next.js 16.4.5**. Para cada una se mide el `src` y el `srcset` del `<img>` que llega en el HTML. Si cambias de CMS a Next.js sin mirar esto, todas tus imágenes pasan de `/img/gato-naranja.jpg` a `/_next/image?url=...&w=1920&q=75`: es una migración de URLs de imagen que nadie ha planificado.

## Ficheros

| Fichero | Qué es |
|---|---|
| [`app/por-defecto/page.js`](app/por-defecto/page.js) | `next/image` tal cual, en un componente de servidor |
| [`loader-carlos.js`](loader-carlos.js) | El loader del artículo de Carlos, copiado sin cambios: devuelve la original en la llamada repetida del último ancho, que es la del `src` |
| [`app/loader-carlos/page.js`](app/loader-carlos/page.js) | Ese loader en dos imágenes (ancho fijo y `sizes="100vw"`). Lleva `'use client'` |
| [`app/override-src/page.js`](app/override-src/page.js) | La prop oficial `overrideSrc` (Next 14.2.0+), sin `'use client'` |
| [`app/sin-optimizar/page.js`](app/sin-optimizar/page.js) | `unoptimized`: la original, sin `srcset` |
| [`extra/loader-en-servidor.js`](extra/loader-en-servidor.js) | El loader pasado desde un componente de servidor; solo se usa en la fase 2 |
| [`prueba.py`](prueba.py) | Monta el proyecto en `%TEMP%\next-img`, hace build, arranca en el puerto 8136 y comprueba |

## Cómo probarlo

Git Bash, con Node.js 24 y Python con Pillow:

```bash
cd ejercicios/imagenes-next
python prueba.py
```

La primera vez hace `npm install` en `%TEMP%\next-img` (unos 40 s); `node_modules` no entra en el repositorio ni en OneDrive.

PowerShell: `cd ejercicios\imagenes-next; python prueba.py`

Salida real (2026-11-07):

```
/por-defecto (HTTP 200)
  src    = /_next/image?url=%2Fimg%2Fgato-naranja.jpg&w=1920&q=75
  srcset = 2 URLs, la primera /_next/image?url=%2Fimg%2Fgato-naranja.jpg&w=828&q=75

/loader-carlos (HTTP 200)
  src    = /img/gato-naranja.jpg
  srcset = 2 URLs, la primera /_next/image?url=%2Fimg%2Fgato-naranja.jpg&w=828&q=75
  src    = /img/perro-marron.jpg
  srcset = 8 URLs, la primera /_next/image?url=%2Fimg%2Fperro-marron.jpg&w=640&q=75

/override-src (HTTP 200)
  src    = /img/gato-naranja.jpg
  srcset = 2 URLs, la primera /_next/image?url=%2Fimg%2Fgato-naranja.jpg&w=828&q=75

/sin-optimizar (HTTP 200)
  src    = /img/gato-naranja.jpg
  srcset = 0 URLs

Qué devuelve el servidor
  [OK] la original responde 200 image/jpeg  -> 200 image/jpeg 114625 B
  [OK] /_next/image: la MISMA URL da WebP o JPEG según Accept  -> image/webp 10420 B / image/jpeg 64044 B

Fase 2: el loader pasado desde un componente de servidor
  [OK] next build falla (exit distinto de 0)  -> exit 1
  [OK] el error es el de pasar una función a un componente de cliente  -> Error: Functions cannot be passed directly to Client Components unless you explicitly expose it by marking it with "use server". ...

13/13 comprobaciones OK
```

## Qué enseña

| Variante | `src` | `srcset` | Coste |
|---|---|---|---|
| Por defecto | `/_next/image?url=…` | optimizado | URL con parámetros, sin nombre de fichero en la ruta |
| Loader de Carlos | original | optimizado | La página tiene que ser `'use client'`; depende del orden en que Next llama al loader |
| `overrideSrc` | original | optimizado | Ninguno: es la vía documentada «for SEO purposes such as image ranking or avoiding recrawl» |
| `unoptimized` | original | ninguno | Se pierde el redimensionado y el WebP |

1. **Por defecto, Next cambia la URL de todas las imágenes.** Es lo que avisa la fila 27 y lo que critica Carlos.
2. **El loader de Carlos funciona en Next 16**, como dice su actualización del 2026-09-30, también con dos imágenes seguidas y un `srcset` de 8 anchos. Pero **solo en un componente de cliente**: en el App Router las páginas son de servidor por defecto, y pasar la función como prop rompe el build.
3. **`overrideSrc` da el mismo resultado sin truco** y sin `'use client'`. Existe desde Next 14.2.0 y el artículo no la menciona.
4. **`/_next/image` negocia el formato por `Accept`**: la misma URL devuelve WebP (10.420 B) o JPEG (64.044 B). Es lo que Carlos señala frente a `<picture>`: el formato no se ve en la URL.

## Lo que no mide

- Qué indexa Google de verdad: el ejercicio mide el HTML, no Google Imágenes. Google dice que encuentra las imágenes en el `src`, que el `srcset` no lo entienden todos los rastreadores (por eso pide siempre un `src`), que el nombre del fichero da pistas «muy leves» y que conviene que la extensión coincida con el tipo. `/_next/image` no tiene extensión y su tipo cambia según `Accept`.
- La hidratación en un navegador (el loader guarda estado en una variable del módulo) ni el modo desarrollo.
- `loaderFile` en `next.config.js`, que aplica el loader a toda la web sin `'use client'` en cada página.
