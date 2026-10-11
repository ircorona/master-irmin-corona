# Indirecciones (direcciones internas)

Ejercicio de la clase 10 de Servidores (Avanzado): **genera una indirección, ya sea en WordPress o en el código normal**. Están las dos.

Una indirección sirve el contenido de una URL interna en otra URL pública, **sin redirigir**: la barra de direcciones no cambia. Para que no haya contenido duplicado, la URL interna no debe responder 200 por su cuenta.

## Qué hay

| Ruta | Qué es |
|---|---|
| `sitio/.htaccess` | Indirección en código normal: `/indireccion/asdrubal/...` muestra `/ejemplo/...` |
| `wp/ejercicio/indireccion-articulo-seo.php` | Indirección en WordPress: los posts se sirven en `/articulo-seo/slug/` |
| `demos/estandar-clase/.htaccess` | La indirección estándar de clase, tal cual |
| `wp/clase/` | El `.htaccess` y el PHP de WordPress de clase, tal cual |
| `wp/instalar.php` | Instala el WordPress de pruebas y regenera sus reglas |
| `prueba.py` | Arranca Apache y un WordPress con SQLite y lo comprueba todo |

## Cómo se ejecuta

```bash
python prueba.py                         # 37 comprobaciones, sale con 0 si todo pasa
python prueba.py --servir ejercicio      # deja Apache y WordPress arrancados
```

Usa el Apache 2.4.62 y el PHP 8.3 de Laragon (el que trae `pdo_sqlite`) con una configuración propia en una carpeta temporal. **No toca el Apache ni el MySQL de Laragon**: WordPress corre sobre SQLite con el plugin oficial SQLite Database Integration. La primera vez descarga WordPress y el plugin de wordpress.org a `.cache/` (fuera de git). Puertos 8091, 8092, 8093 y 9193.

## En código normal

```apache
<IfModule mod_rewrite.c>
RewriteEngine On

# 0. Directorios sin barra final -> con barra, con 301 a la URL pública
RewriteRule ^indireccion/asdrubal$ /indireccion/asdrubal/ [R=301,L]
RewriteCond %{DOCUMENT_ROOT}/ejemplo/$1 -d
RewriteRule ^indireccion/asdrubal/(.*[^/])$ /indireccion/asdrubal/$1/ [R=301,L]

# 1. La indirección
RewriteRule ^indireccion/asdrubal/(.*)$ /ejemplo/$1 [END]

# 2. La URL interna pedida directamente: 404
RewriteCond %{THE_REQUEST} ^[A-Z]+\s/ejemplo(/|\s|\?) [NC]
RewriteRule ^ - [R=404]
</IfModule>
```

Tres cambios sobre el código de clase, los tres medidos:

| Código de clase | Qué pasa | Corrección |
|---|---|---|
| Sin regla para la barra final | `/indireccion/asdrubal` → `mod_dir` redirige a **`/ejemplo/`**, que enseña la URL interna y da 404 | Regla 0 |
| `asdrubal(.*)` | `/indireccion/asdrubals-de-uso/` sirve **otra sección**, `/ejemplos-de-uso/` | Barra obligatoria tras `asdrubal` |
| `\s/ejemplo` sin anclar | `/ejemplos-de-uso/`, una sección legítima, da **404** | `/ejemplo(/\|\s\|\?)` |

## En WordPress

```php
add_action('init', function () {
    add_rewrite_rule('^articulo-seo/([^/]+)/?$', 'index.php?name=$matches[1]', 'top');
});

add_filter('post_link', function ($url, $post) {
    if ($post->post_type !== 'post' || $post->post_status !== 'publish') {
        return $url;
    }
    return home_url('/articulo-seo/' . $post->post_name . '/');
}, 10, 2);

add_action('template_redirect', function () {
    if (!is_singular('post') || is_preview()) {
        return;
    }
    $ruta = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (strpos($ruta, '/articulo-seo/') !== 0) {
        wp_redirect(get_permalink(), 301);
        exit;
    }
});
```

Va en `wp-content/mu-plugins/` o en el `functions.php` del tema. Después: **Ajustes → Enlaces permanentes → Guardar**, que regenera las reglas.

Lo que mide `prueba.py` sobre un WordPress 7.1.3 con enlaces `/articulo/%postname%/`:

| Escenario | Resultado |
|---|---|
| Solo el `.htaccess` de clase | `/articulo-seo/mi-post/` **404**: WordPress lee la URL original, no la reescrita. Y `/articulo/mi-post/` redirige a ese 404 |
| Solo el PHP de clase | Funciona, pero `/articulo/mi-post/` también da 200 (duplicado) y el canonical de `/articulo-seo/` apunta a `/articulo/` |
| `.htaccess` + PHP de clase | `/articulo/` redirige a `/articulo-seo/`, cuyo canonical apunta de vuelta a `/articulo/`. Los enlaces internos siguen en `/articulo/` |
| **Ejercicio** | `/articulo-seo/mi-post/` 200 con canonical propio; `/articulo/mi-post/` y `?p=1` 301 a la pública; enlaces, sitemap y feed en `/articulo-seo/` |

El 301 de la URL interna descarta los parámetros (`?utm_source=...`). Para conservarlos habría que añadir `$_SERVER['QUERY_STRING']` al destino.
