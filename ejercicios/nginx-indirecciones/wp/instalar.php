<?php
// Instala un WordPress de pruebas (SQLite) o vuelve a generar sus reglas.
// Lo llama prueba.py con el php.exe de Laragon:
//   php instalar.php <carpeta-wp> <url> instalar
//   php instalar.php <carpeta-wp> <url> reglas     (tras cambiar los mu-plugins)
[$_, $dir, $url, $accion] = $argv;

$_SERVER['HTTP_HOST'] = parse_url($url, PHP_URL_HOST) . ':' . parse_url($url, PHP_URL_PORT);
$_SERVER['REQUEST_URI'] = '/';
define('WP_INSTALLING', $accion === 'instalar');
require $dir . '/wp-load.php';
// Sin correos: el sendmail de Laragon (Mailpit) no está arrancado
add_filter('pre_wp_mail', '__return_false');

if ($accion === 'instalar') {
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    // true = sitio público: si no, WordPress no genera wp-sitemap.xml
    wp_install('Indirecciones', 'admin', 'admin@example.com', true, '', wp_generate_password(24));
    update_option('siteurl', $url);
    update_option('home', $url);
    update_option('permalink_structure', '/articulo/%postname%/');
    wp_insert_post([
        'post_title'   => 'Mi post',
        'post_name'    => 'mi-post',
        'post_content' => 'CONTENIDO DEL POST mi-post',
        'post_status'  => 'publish',
    ]);
}

// Regenera las reglas con los mu-plugins que haya ahora (rewrite_rules_array
// solo se aplica al regenerarlas: es el "Guardar" de Ajustes > Enlaces permanentes).
flush_rewrite_rules(false);
echo "ok $accion\n";
