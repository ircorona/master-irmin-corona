<?php
// Borrar el Sitemap por defecto de WordPress (clase 09 sitemaps).
// 1) El filtro impide que el core genere wp-sitemap.xml.
// 2) Por si acaso, retiramos también la acción que monta el servidor de sitemaps.
add_filter('wp_sitemaps_enabled', '__return_false');
if (has_action('init', 'wp_sitemaps_get_server')) {
    remove_action('init', 'wp_sitemaps_get_server');
}

?>