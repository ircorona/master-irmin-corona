<?php
/**
 * Asdrubal Theme Functions
 */

$templates = __DIR__ . '/templates/';

// Theme setup
function asdrubal_theme_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('automatic-feed-links');

    // Ubicación de menú para la navegación principal (gestionable en Apariencia > Menús).
    register_nav_menus(array(
        'primary' => __('Menú principal', 'asdrubal'),
    ));
}
add_action('after_setup_theme', 'asdrubal_theme_setup');

// Register templates from the templates folder
function asdrubal_register_templates($templates) {
    $template_dir = get_template_directory() . '/templates/';

    if (is_dir($template_dir)) {
        $files = scandir($template_dir);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $full_path = $template_dir . $file;
                $template_data = get_file_data($full_path, array('Template Name' => 'Template Name'));
                if (!empty($template_data['Template Name'])) {
                    $templates['templates/' . $file] = $template_data['Template Name'];
                }
            }
        }
    }
    return $templates;
}
add_filter('theme_page_templates', 'asdrubal_register_templates');

// Load templates from the templates folder
function asdrubal_load_template($template) {
    global $post;

    if ($post) {
        $page_template = get_post_meta($post->ID, '_wp_page_template', true);
        if (strpos($page_template, 'templates/') === 0) {
            $file = get_template_directory() . '/' . $page_template;
            if (file_exists($file)) {
                return $file;
            }
        }
    }
    return $template;
}
add_filter('template_include', 'asdrubal_load_template');

// Enqueue theme styles
function asdrubal_enqueue_styles() {
    wp_enqueue_style(
        'asdrubal-style',
        get_stylesheet_uri(),
        array(),
        '2.1',
        'all'
    );
}
add_action('wp_enqueue_scripts', 'asdrubal_enqueue_styles');



// Datos estructurados (clase 07): el TEMA emite Organization y el artículo/coche
// desde components/datos-estructurados.php, porque solo él conoce los campos ACF
// del coche. Se le dice al plugin de la clase 06 que no los duplique: dos marcados
// del mismo tipo en la misma página son dos entidades contradictorias.
// El plugin sigue encargándose de FAQPage, Product y BreadcrumbList.
add_filter('irmin_datos_estructurados_omitir', function ($omitir, $tipo) {
    return in_array($tipo, array('organization', 'article'), true) ? true : $omitir;
}, 10, 2);


// Borrar el Sitemap por defecto de WordPress (clase 09 sitemaps).
// 1) El filtro impide que el core genere wp-sitemap.xml.
// 2) Por si acaso, retiramos también la acción que monta el servidor de sitemaps.
//add_filter('wp_sitemaps_enabled', '__return_false');
//if (has_action('init', 'wp_sitemaps_get_server')) {
//    remove_action('init', 'wp_sitemaps_get_server');
//}
