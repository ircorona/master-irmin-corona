# Ejercicio: configuración para móvil de 17 webs

**Ejercicio:** SEO para móviles — 03 Configuraciones
**Fecha de las comprobaciones:** 6 de agosto de 2026

## Método

Dos peticiones a cada web, una con user-agent de móvil y otra de escritorio, comparando **URL final**, **tamaño del HTML**, **cabecera `Vary`** y **etiquetas `canonical` / `alternate`**:

```bash
UA_M="Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Mobile Safari/537.36"
UA_D="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36"

curl -s -o m.html -w "%{http_code}|%{url_effective}|%{size_download}" -A "$UA_M" -L "$URL"
curl -s -o d.html -w "%{http_code}|%{url_effective}|%{size_download}" -A "$UA_D" -L "$URL"
curl -sI -A "$UA_M" -L "$URL" | grep -i "^vary"
grep -oE '<link[^>]*rel="(canonical|alternate)"[^>]*>' d.html
```

Criterio de decisión:

| Señal observada | Configuración |
|---|---|
| La URL final cambia a `m.dominio`, o hay `rel="alternate" media=…` | **Por dominio** (URL independientes) |
| Misma URL, HTML claramente distinto por user-agent | **Adaptive** |
| Misma URL, mismo HTML (diferencia < 3 %, atribuible a contenido dinámico) | **Responsive** |

## Resultados

| # | Web | Configuración | Evidencia |
|---|---|---|---|
| 1 | **amovens.com** | **Responsive** | Misma URL. 244.740 B (móvil) vs 244.712 B (escritorio) — **28 bytes**, un nonce. Mismo `canonical` |
| 2 | **es.aliexpress.com** | **Adaptive** | Misma URL, mismo `canonical`, pero **55.377 B vs 78.167 B**. La móvil añade `minimum-scale` al viewport |
| 3 | **ebay.com** | **Adaptive** | Misma URL, **295.192 B vs 413.358 B**. `m.ebay.com` **redirige a `www.ebay.com`** y sirve ahí la versión móvil |
| 4 | **cloudflare.com** | **Responsive** | **1.304.716 B exactos en ambos**. `Vary: accept-encoding` (sin User-Agent) |
| 5 | **stackoverflow.com** | **Responsive** ⚠️ | Bloquea `curl` (403 en los dos). Viewport idéntico. Evidencia débil, conclusión por inspección |
| 6 | **nh-hotels.com** | **Adaptive** ⚠️ | Bloquea escritorio (403). Declara **`Vary: User-Agent`**, que es la firma del adaptive. Evidencia parcial |
| 7 | **imdb.com** | **Adaptive** ⚠️ | Bloquea `curl` (202 sin cuerpo). Declara **`Vary: Accept-Encoding,User-Agent`**. Evidencia parcial |
| 8 | **microsoft.com/es-es** | **Responsive** ⚠️ | Primera tanda: **201.253 B idénticos** en ambos. Con otras cabeceras devuelve páginas de desafío distintas. Me quedo con la medición limpia |
| 9 | **nytimes.com** | **Responsive** | 1.355.104 B vs 1.381.802 B (**2 %**, contenido dinámico). `Vary` sin User-Agent. `@media` en el HTML |
| 10 | **habitaclia.com** | **Por dominio** | El agente móvil **acaba en `https://m.habitaclia.com/`**. El `canonical` de la móvil apunta a `www.habitaclia.com/` |
| 11 | **softonic.com** | **Responsive** | **73.272 B idénticos**. Mismo `canonical`, mismo viewport |
| 12 | **es.wikipedia.org** | **Adaptive** | Misma URL, mismo `canonical`, pero el viewport cambia: **`width=1120` en escritorio y `width=device-width` en móvil**. `Vary: …,User-Agent`. `es.m.wikipedia.org` **redirige a `es.wikipedia.org`** |
| 13 | **open.spotify.com** | **Adaptive** | Misma URL, **297.258 B (móvil) vs 156.122 B (escritorio)**: casi el doble |
| 14 | **airbnb.es** | **Responsive** | 554.583 B vs 624.778 B (11 %, atribuible al contenido dinámico). **23 bloques `@media`** en el propio HTML, mismo `canonical`, sin `Vary: User-Agent` |
| 15 | **easyfiv.es** | **Por dominio** | Declara **`<link rel="alternate" media="only screen and (max-width: 640px)" href="https://m.easyfiv.es">`**. `m.easyfiv.es` existe (228.356 B) y su `canonical` apunta a `easyfiv.es/`. Es el par completo del manual |
| **Valientes** | | | |
| 16 | **filmaffinity.com** | **Por dominio** | El agente móvil acaba en **`m.filmaffinity.com/mx/main.php`**; el de escritorio, en `www.filmaffinity.com/mx/main.html`. Escritorio declara el `alternate`, la móvil el `canonical` de vuelta. `Vary: User-Agent` |
| 17 | **parquewarner.com** | **Responsive** | **174.572 B idénticos**. Mismo `canonical` |

**Resumen:** 8 responsive · 5 adaptive · 3 por dominio (+1 responsive con evidencia débil).

### Hallazgos extra, fuera de lo que pedía el ejercicio

- **parquewarner.com** — su viewport lleva **`maximum-scale`**: limita el zoom del usuario. Fallo de accesibilidad (WCAG 1.4.4).
- **habitaclia.com** — tiene **dos `<meta name="viewport">` duplicadas** en la misma página, **y las dos con `maximum-scale=1`**. Duplicado + bloqueo de zoom.
- **es.wikipedia.org** — el caso más interesante: sirve `width=1120` al escritorio, es decir, **un viewport de ancho fijo**, no responsive. Y su antiguo dominio móvil ya no es un destino, sino una redirección de vuelta.

## ¿Qué fallo tiene `m.shein.com/es/`?

**El `canonical` de la versión móvil apunta a la home, no a su página equivalente de escritorio.**

```html
<!-- en https://m.shein.com/es/ -->
<link rel="canonical" href="https://www.shein.com/" />
```

Debería apuntar a la URL de escritorio **equivalente** (`https://www.shein.com/es/`). Al canonicalizar a la home:

1. Le está diciendo a Google que **esa página es un duplicado de la portada**, así que la portada absorbe la señal y la página `/es/` deja de competir por sí misma.
2. Si el mismo patrón se repite en todas las URLs de `m.shein.com`, **el sitio móvil entero se canonicaliza a una sola URL** — y el resto desaparece del índice.

Y hay tres cosas más que agravan el mismo problema de configuración:

| # | Problema | Comprobación |
|---|---|---|
| 2 | **Falta el `rel="alternate"`** en la versión de escritorio. El par de las URL independientes está **manco**: la móvil apunta a la de escritorio, pero la de escritorio no declara cuál es su móvil | Sin `alternate` en el HTML de `www.shein.com` |
| 3 | **La URL de escritorio equivalente devuelve `404`** | `https://www.shein.com/es/` con agente de escritorio → redirige a `www.shein.com.mx/es/` → **404**. Se canonicaliza hacia páginas que no existen |
| 4 | **`m.shein.com` devuelve `403` a un agente de escritorio** | `curl -A "<UA escritorio>" https://m.shein.com/es/` → **403**. Googlebot Desktop, que sigue rastreando para fichas de producto, se queda fuera |

En una frase: **Shein tiene una configuración de URL independientes con el par `canonical`/`alternate` roto** — la móvil canonicaliza a la home, la de escritorio no declara su alternativa, y la URL a la que apunta ni siquiera responde.

## Avisos sobre la medición

- **Las pruebas salen desde una IP enrutada a México.** Por eso Shein redirige a `.com.mx` y FilmAffinity a `/mx/`. Desde España los destinos serían otros, aunque **la configuración detectada no cambia**.
- **Cinco webs bloquean `curl`** (StackOverflow, IMDb, NH Hotels, Softonic y Microsoft en algún intento), y devuelven páginas de desafío en vez de la web. Están marcadas con ⚠️ y su conclusión es menos firme.
- Las marcadas con ⚠️ conviene confirmarlas en el navegador con DevTools, cambiando el user-agent a mano.

## Pendiente

El enunciado pide **enviar las respuestas a `carlos@sanchezdonate.com`**. No lo he redactado: dilo y lo preparo.

> Nota: `carlos.sanchezdonate.com` redirige ahora entero a `sanchezdonate.net`. La dirección de correo no tiene por qué haber cambiado, pero conviene tenerlo en cuenta.
