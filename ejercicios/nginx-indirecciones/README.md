# Indirecciones en nginx

Ejercicio de la clase 20 (NGINX indirecciones) de Servidores (Avanzado): prueba a hacer una indirección. Están las dos, en código normal y en WordPress.

Una indirección sirve el contenido de una URL interna en otra URL pública, **sin redirigir**: la barra de direcciones no cambia. Para que no haya contenido duplicado, la URL interna no debe responder 200 por su cuenta.

## Qué hay

| Ruta | Qué es |
|---|---|
| `conf/clase.conf` | La indirección básica y la de proyecto PHP (`permanent` y `last`), tal cual |
| `conf/ejercicio.conf` | Indirección en código normal: `/indireccion/asdrubal/...` muestra `/ejemplo/...` |
| `wp/clase/` | Las dos reglas de WordPress de clase y el PHP (el mismo de la clase 10) |
| `wp/ejercicio/indireccion-articulo-seo.php` | La indirección hecha en WordPress (el mu-plugin de la clase 10) |
| `wp/nginx-wp.conf` | El `server` de WordPress, donde `prueba.py` pone las reglas de cada escenario |
| `prueba.py` | Arranca nginx, php-cgi y un WordPress con SQLite y lo comprueba todo |

## Cómo se ejecuta

```bash
python prueba.py                        # 38 comprobaciones, sale con 0 si todo pasa
python prueba.py --servir ejercicio     # deja nginx y WordPress arrancados
```

Usa el nginx 1.27.3 y el PHP 8.3 de Laragon (el que trae `pdo_sqlite`). No toca el nginx, el Apache ni el MySQL de Laragon. WordPress y el plugin SQLite se copian de `../indirecciones/.cache/` o se descargan a `.cache/` (fuera de git). Puertos 8006, 8007, 8008, 8010 y 9108.

## En código normal

```nginx
location = /indireccion/asdrubal {
    return 301 /indireccion/asdrubal/;
}

location ~ ^/indireccion/asdrubal/(?<resto>.*)$ {
    if (-d $document_root/ejemplo/$resto) {
        rewrite ^(.*[^/])$ $1/ permanent;
    }
    rewrite ^ /ejemplo/$resto last;
}

location ^~ /ejemplo/ {
    internal;
}
location = /ejemplo {
    return 404;
}
```

| Prueba | Resultado |
|---|---|
| `/indireccion/asdrubal/` y `/indireccion/asdrubal/sub/` | 200 con el contenido de `/ejemplo/` |
| Sin barra final | 301 a la URL **pública** con barra |
| `/ejemplo/`, sus subcarpetas, sus ficheros, `/ejemplo` | 404: `internal` solo deja entrar a las peticiones que vienen de un `rewrite` |
| `/ejemplos-de-uso/` | 200: no es la carpeta interna |

## En WordPress

Sin reglas de nginx: la indirección va en WordPress, con el mu-plugin de la clase 10 (`add_rewrite_rule` + `post_link` + 301 desde `template_redirect`). Funciona igual en Apache que en nginx. Después de instalarlo: Ajustes → Enlaces permanentes → Guardar.

## Lo que enseña el código de clase

| Código | Medido |
|---|---|
| Indirección básica, sitio estático | Funciona, pero **`/articulo/mi-post/` también da 200** (duplicado). Y `/articulo-seo/mi-post` sin barra redirige a **`/articulo/mi-post/`**: nginx añade la barra con la URL interna |
| `wpo.php` con `permanent` | Es un 301, no una indirección. El `.` sin escapar casa también con `/wpoXphp` |
| `wpo.php` con `last` | Indirección, pero `sobre-mi.php` sigue en 200 |
| Indirección básica en WordPress | **404**: WordPress lee la URL original (`$request_uri`), no la reescrita |
| «Exitosa» sin el PHP | `/articulo/mi-post/` redirige a un **404** |
| «Exitosa» + PHP | Funciona, pero el canonical, los enlaces y el sitemap siguen en `/articulo/`, que redirige. `/articulo-seo/mi-post/feed/` da 404 |
