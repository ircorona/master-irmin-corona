<?php
/**
 * Plugin Name: Mi SEO
 * Description: Lo mínimo de un plugin de SEO propio: meta description y JSON-LD Article en las entradas. Se apaga solo si hay Yoast o Rank Math.
 * Version: 1.0.0
 * Requires at least: 6.5
 * Requires PHP: 8.1
 */

if (!defined('ABSPATH')) {
    exit; // Nunca se ejecuta suelto, solo dentro de WordPress
}

// Un solo plugin de SEO: si ya hay otro, este no imprime nada.
function mi_seo_hay_otro_plugin(): bool
{
    return defined('WPSEO_VERSION') || class_exists('RankMath');
}

add_action('wp_head', function () {
    if (mi_seo_hay_otro_plugin() || !is_singular('post')) {
        return;
    }
    $post = get_queried_object();
    $descripcion = wp_strip_all_tags(get_the_excerpt($post));

    // El canonical lo pone el núcleo de WordPress (rel_canonical): no lo repetimos.
    printf("<meta name=\"description\" content=\"%s\">\n", esc_attr($descripcion));
}, 1);

add_action('wp_footer', function () {
    if (mi_seo_hay_otro_plugin() || !is_singular('post')) {
        return;
    }
    $post = get_queried_object();
    $datos = [
        '@context'      => 'https://schema.org',
        '@type'         => 'Article',
        'headline'      => get_the_title($post),
        'datePublished' => get_the_date('c', $post),
        'dateModified'  => get_the_modified_date('c', $post),
        'url'           => get_permalink($post),
    ];
    // JSON-LD abajo del todo, como recomienda Carlos
    echo '<script type="application/ld+json">' . wp_json_encode($datos) . "</script>\n";
});
