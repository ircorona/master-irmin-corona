# Ejercicio: dos versiones de una página, móvil y ordenador (AWD)

**Ejercicio:** SEO para móviles — 08 Código AWD
**Enunciado:** *"Haz en tu página de prueba dos versiones distintas, una para móvil y otra para ordenador (con que se vea un «hola mundo» en la versión móvil me vale)."*
**URL local:** `https://master-irmin-corona.test/ejercicios/awd-devices/`
**Fecha de las comprobaciones:** 9 de agosto de 2026

## Qué hay aquí

```
ejercicios/awd-devices/
├── .htaccess                    reglas de dynamic serving por User-Agent
├── index.php                    versión ESCRITORIO (el caso por defecto)
├── devices/
│   ├── movil/index.php          versión MÓVIL — "Hola mundo"
│   └── tablet/index.php         versión TABLET
├── deteccion-css.php            variante mixta: un HTML, tres hojas de estilo
└── assets/css/                  escritorio.css · movil.css · tablet.css
```

Dos implementaciones del mismo concepto:

| Enfoque | Qué cambia según el dispositivo | Riesgo SEO |
|---|---|---|
| **`.htaccess` + `devices/`** | **El HTML entero** | Medio: hay que vigilar que el contenido no diverja |
| **`deteccion-css.php`** | **Solo la hoja de estilos** | Bajo: el HTML es idéntico para todos |

La estructura de carpetas `devices/movil/` y `devices/tablet/` es la que montó Carlos en clase.

## Lo que hace que esto sea AWD y no "URLs independientes"

La reescritura del `.htaccess` es **interna**: `[L]`, nunca `[R]`.

```apache
RewriteRule ^(.*)$ devices/movil/$1 [L]
```

La URL en la barra del navegador **no cambia**. Un móvil pide `/ejercicios/awd-devices/` y recibe el HTML de `devices/movil/index.php` **en esa misma URL**. Comprobado: la respuesta es `200`, sin `Location`, sin redirección.

De ahí se sigue lo importante para SEO: **una sola URL que indexar**, y por tanto **no hace falta el par `canonical`/`alternate`**. Si en vez de `[L]` se pusiera `[R=302]`, esto dejaría de ser adaptive y pasaría a ser una configuración de URLs independientes, con todo su mantenimiento — que es donde se rompen sitios como Shein ([ejercicio 03](../seo-movil-configuraciones/respuestas.md)).

## `Vary: User-Agent` — la línea que no estaba en el código de auxilio

```apache
<IfModule mod_headers.c>
    Header append Vary "User-Agent"
</IfModule>
```

Es **obligatoria** en cualquier configuración adaptive, y el código de la clase no la incluye. Sin ella:

- Una caché intermedia (proxy, CDN, incluso el propio navegador) puede guardar la versión móvil y **servírsela a un ordenador**.
- Google no sabe que la URL cambia de contenido según el agente, y puede no rastrearla con sus dos Googlebot.

`append` y no `set`, para no pisar un `Vary: Accept-Encoding` que ya venga del servidor.

## Comprobación

Probado contra Laragon con seis agentes distintos. Todos devuelven `200` sin redirección:

```bash
U="https://master-irmin-corona.test/ejercicios/awd-devices/"
curl -sk -A "$UA" "$U" | grep -o '<title>[^<]*</title>'
```

| Agente | Versión servida |
|---|---|
| Windows / Chrome | ✅ **Escritorio** |
| iPhone / Safari | ✅ **Móvil** |
| Android Pixel 7 (con `Mobile`) | ✅ **Móvil** |
| Android SM-X200 (tablet, sin `Mobile`) | ✅ **Tablet** |
| iPad / Safari | ✅ **Tablet** |
| **Googlebot smartphone** | ✅ **Móvil** |

```
$ curl -skI -A "…iPhone…" "$U" | grep -iE "^(HTTP|vary)"
HTTP/1.1 200 OK
Vary: User-Agent
```

Y la variante mixta, con los mismos agentes:

| Agente | Hoja enlazada |
|---|---|
| Escritorio | `assets/css/escritorio.css` |
| iPhone · Android móvil | `assets/css/movil.css` |
| Android tablet · iPad | `assets/css/tablet.css` |

## Fallos del código de auxilio, y cómo se han corregido

El código que da la clase funciona a medias. Cinco cosas:

### 1. `Macintosh|Mac OS X` en la regla de tablet manda todos los Mac a la tablet

```apache
RewriteCond %{HTTP_USER_AGENT} "ipad|tablet|Macintosh|Mac OS X|kindle|playbook|silk" [NC]
```

**No es un despiste, es un dilema real**: desde iPadOS 13, Safari en el iPad viene con «Solicitar sitio para ordenador» activado por defecto y se identifica como `Macintosh; Intel Mac OS X`. Por User-Agent, **un iPad es indistinguible de un MacBook**.

El precio de resolverlo así es que **todos los usuarios de Mac de escritorio reciben la versión de tablet**. En este ejercicio se ha quitado: el iPad en modo escritorio verá la versión de ordenador, que es el mal menor. Si de verdad hace falta distinguirlos, la única vía es JavaScript en cliente (`navigator.maxTouchPoints > 1` en un `Macintosh`), no el servidor.

### 2. El orden de las reglas deja la de tablet muerta

En el original la regla de móvil va primero y se excluye con `!ipad` y `!tablet`. Funciona por los pelos, pero se rompe con las tablets Android, que llevan `android` en las dos listas. Aquí se comprueba **primero la tablet, después el móvil**, y se separan las Android por el token `Mobile`:

```apache
# Tablet Android: lleva "Android" pero NO lleva "Mobile"
RewriteCond %{HTTP_USER_AGENT} "android" [NC]
RewriteCond %{HTTP_USER_AGENT} !mobile [NC]
RewriteRule ^(.*)$ devices/tablet/$1 [L]
```

Ese es el criterio real: **un teléfono Android lleva `Mobile` en el UA; una tablet Android, no.**

### 3. La versión de nginx entra en bucle infinito

```nginx
if ($http_user_agent ~* "android|iphone|…") {
    rewrite ^(.*)$ /miversion/movil/$1 last;
}
```

**No tiene el guardia de la versión de Apache.** El `RewriteCond %{REQUEST_URI} !^/devices/movil/` no está en ningún sitio, así que la petición reescrita vuelve a entrar en `location /`, el agente sigue siendo móvil, y se reescribe otra vez. nginx corta a las 10 vueltas con un **500 "rewrite or internal redirection cycle"**.

Además, la regla de tablet **nunca se alcanza para Android**: `android` está también en la lista de móvil, y `last` sale del bloque en la primera coincidencia. El equivalente correcto usa `map` en lugar de `if` — `if` dentro de `location` está desaconsejado en la propia documentación de nginx.

### 4. El PHP mixto tiene el mismo problema de orden

```php
$isMobile = preg_match('/android|blackberry|ipad|iphone|…/i', $userAgent);   // ← "ipad" aquí
$isTablet = preg_match('/android|ipad|tablet|kindle|playbook|silk/i', $userAgent);

if ($isMobile) { … } elseif ($isTablet) { … }   // el elseif no se ejecuta nunca para iPad
```

`ipad` y `android` están en **las dos** expresiones regulares, y `$isMobile` se evalúa primero en el `if`. Resultado: **`$tabletCSS` es código muerto**. Solo llegarían a la rama de tablet un Kindle, una PlayBook o un Silk. La versión corregida está en [`deteccion-css.php`](deteccion-css.php).

### 5. Falta `Vary: User-Agent` en las tres versiones

Ya explicado arriba. En PHP:

```php
header('Vary: User-Agent');   // antes de cualquier salida
```

### Extra: `googlebot-mobile` está retirado

El agente `Googlebot-Mobile` se retiró en 2016. El Googlebot smartphone actual se identifica como Android + `Mobile`, así que la propia lista de móviles ya lo captura — comprobado arriba. La cadena se ha quitado por ruido, no por daño.

## Sobre el conflicto de reglas que avisa el enunciado

El aviso de la clase es que estas reglas pueden chocar con la regla de 404 sobre `.php` de ejercicios anteriores. En este repo **no se ha dado el problema**, por dos razones:

1. El `.htaccess` de la raíz solo tiene: forzar HTTPS, el 404 de `wp-sitemap*.xml` y el bloque de WordPress. Esa regla de `THE_REQUEST` no está.
2. **Este `.htaccess` está en una subcarpeta, no en la raíz.** En cuanto se escribe `RewriteEngine On` en un `.htaccess` de directorio, mod_rewrite **deja de heredar** las reglas del padre dentro de esa carpeta. Aislamiento gratis.

Y es a propósito: estas reglas **en la raíz del sitio romperían WordPress**, porque `^(.*)$` reescribiría también `/wp-admin/`, `/wp-json/` y `/wp-login.php` hacia `devices/movil/`, que no existen.

El único fallo que sí apareció al probar fue propio: la regla capturaba también `deteccion-css.php` y lo mandaba a `devices/movil/deteccion-css.php` → **404**. Corregido con una exclusión explícita antes de las reglas de dispositivo.

## Cuándo usar esto de verdad

Casi nunca. La [clase 07](../../knowledge/seo-para-moviles/07-codigo-rwd.md) ya deja claro que el responsive es más barato de mantener. El AWD tiene sentido cuando:

- **Hay equipos separados** que pueden dedicarse a cada versión — el argumento de la clase. Con un equipo pequeño, mantener dos plantillas es multiplicar el trabajo por dos.
- La experiencia móvil es **funcionalmente distinta**, no una reordenación: un banco, una aerolínea.
- Hay que servir a dispositivos viejos que no entienden media queries.

Y siempre con las tres condiciones: reescritura interna (no redirección), `Vary: User-Agent`, y **el mismo contenido principal en las dos versiones** — porque con mobile-first indexing, lo que falte en la versión móvil es lo que falta en el índice.
