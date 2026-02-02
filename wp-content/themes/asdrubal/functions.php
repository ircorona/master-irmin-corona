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
