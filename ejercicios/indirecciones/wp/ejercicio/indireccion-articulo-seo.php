<?php
/**
 * Plugin Name: Indirección /articulo-seo/
 * Description: Ejercicio de la clase 10 (Servidores avanzado). Los posts se
 *              sirven en /articulo-seo/slug/ y WordPress genera esa URL en
 *              todas partes (canonical, enlaces, sitemap, feed).
 *
 * Va en wp-content/mu-plugins/ o en el functions.php del tema. Después de
 * instalarlo: Ajustes > Enlaces permanentes > Guardar (regenera las reglas).
 */

// 1. La indirección: /articulo-seo/slug/ carga el post "slug".
//    ([^/]+) y no (.*): un solo segmento, sin la barra final dentro del nombre.
add_action('init', function () {
    add_rewrite_rule('^articulo-seo/([^/]+)/?$', 'index.php?name=$matches[1]', 'top');
});

// 2. Una sola URL: WordPress escribe /articulo-seo/ en el canonical, los
//    enlaces, el sitemap y el feed.
add_filter('post_link', function ($url, $post) {
    if ($post->post_type !== 'post' || $post->post_status !== 'publish') {
        return $url; // borradores y previsualizaciones: la URL de siempre
    }
    return home_url('/articulo-seo/' . $post->post_name . '/');
}, 10, 2);

// 3. La URL interna, pedida directamente, redirige con 301 a la pública.
//    redirect_canonical() no lo hace: /articulo/slug/ casa con la regla de
//    los enlaces permanentes y WordPress la da por buena (200, duplicado).
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
