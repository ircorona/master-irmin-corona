# Metaetiquetas en la cabecera HTTP: `X-Robots-Tag` y `Link`

Ejercicio de **Servidores (Avanzado), clase 07 X-Robots**.

## El enunciado

1. Pon un canonical en una página específica con x-robots.
2. Pon noindex en un directorio, que afecte a todas sus subpáginas.

Un matiz sobre el punto 1: el canonical **no** es una directiva de `X-Robots-Tag`. Viaja en otra cabecera, `Link`, con `rel="canonical"`. Las dos se ponen igual, con `Header set` de `mod_headers`.

## La solución

En [`sitio/.htaccess`](sitio/.htaccess):

```apache
<IfModule mod_headers.c>

# 1. Canonical en /sobre-mi/ (y solo ahí)
<If "%{THE_REQUEST} =~ m#^[A-Z]+ /sobre-mi/?[ ?]#">
Header set Link "<https://master-irmin-corona.test/sobre-mi/>; rel=\"canonical\""
</If>

# 2. noindex en /privado/ y todo lo que cuelga, PDFs incluidos
<If "%{THE_REQUEST} =~ m#^[A-Z]+ /privado(/|[ ?])#">
Header set X-Robots-Tag "noindex"
</If>

</IfModule>
```

| Detalle | Por qué |
|---|---|
| `THE_REQUEST` y no `REQUEST_URI` | `REQUEST_URI` cambia en las redirecciones internas: al pedir `/sobre-mi/`, cuando Apache añade las cabeceras ya vale `/sobre-mi/index.html` (en WordPress, `/index.php`). Un patrón anclado sobre él no casa nunca. **Medido**, ver demos |
| `^[A-Z]+ /sobre-mi/?[ ?]` | `THE_REQUEST` es `GET /sobre-mi/?x=1 HTTP/1.1`. Tras la ruta solo puede venir un espacio o `?`: `/sobre-mi-equipo/` no casa, y con parámetros sí |
| `/privado(/\|[ ?])` | Todo lo que cuelga de `/privado/`, pero no `/privados/` ni `/privado-old/` |
| `rel=\"canonical\"` | El estándar de `Link` (RFC 8288) pide comillas **dobles**; dentro de un valor entre comillas de Apache se escapan con `\` |
| URL absoluta | Lo pide Google para el canonical por cabecera |
| `<IfModule>` | Sin `mod_headers`, `Header` es una directiva desconocida y da 500 en toda la web |

Para que el `noindex` funcione, `/privado/` **no** puede estar bloqueado en `robots.txt`: si Google no rastrea la URL, nunca ve la cabecera.

## Las demos de clase

[`demos/.htaccess`](demos/.htaccess) lleva el código de la foto tal cual:

| Demo | Resultado medido |
|---|---|
| `Header set X-Asdrubal "..."` | Sale en todas las respuestas (como el `X-Recruiting` de booking.com) |
| `<If "%{THE_REQUEST} =~ m# /sobre-mi#i">` | `/sobre-mi/` recibe el `noindex`... y **`/sobre-mi-equipo/` también**: el patrón no está anclado por la derecha |
| `rel='canonical'` | Apache lo envía tal cual, con comillas simples: no es la sintaxis del estándar |
| Canonical a `https://carlos.sanchezdonate.com` | Ese dominio hoy responde **301** a `sanchezdonate.net`: un canonical que apunta a una redirección |
| `<If "%{REQUEST_URI} =~ m#^/contacto/?$#">` | **No casa** al pedir `/contacto/`: la ruta ya es `/contacto/index.html` |
| `RewriteRule ^tag/ /wp.html` + `<If "%{REQUEST_URI} =~ m#^/tag/.+/#">` | **No casa**: igual que WordPress con `index.php`, la ruta ya es `/wp.html`. La misma regla con `THE_REQUEST` sí |
| `<Files ~ "\.(avif\|webp\|svg)$">` (artículo de Carlos) | `logo.svg` recibe `X-Robots-Tag: unavailable_after: ...` |

## Cómo probarlo

```bash
python prueba.py
```

Levanta un Apache de usar y tirar con el binario de Laragon, con config propia en una carpeta temporal y en los puertos 8097 (sitio) y 8096 (demos). Pide cada URL y comprueba el código y las cabeceras `Link`, `X-Robots-Tag` y `X-Asdrubal`, incluida su **ausencia** donde no deben salir.

Resultado: **15/15 comprobaciones OK, exit 0**.

En el sitio real, la comprobación es la misma con `curl`:

```bash
curl -skI https://master-irmin-corona.test/privado/informe/ | grep -i x-robots-tag
```
