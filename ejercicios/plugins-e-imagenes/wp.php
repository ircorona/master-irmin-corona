<?php
// Acciones dentro del WordPress de pruebas. Lo llama prueba.php con el PHP 8.3:
//   php wp.php <carpeta-wp> <url> instalar
//   php wp.php <carpeta-wp> <url> activar [plugin/fichero.php ...]
//   php wp.php <carpeta-wp> <url> imagen <ruta-imagen>
[$_, $dir, $url, $accion] = $argv;
$args = array_slice($argv, 4);

$_SERVER['HTTP_HOST'] = parse_url($url, PHP_URL_HOST) . ':' . parse_url($url, PHP_URL_PORT);
$_SERVER['REQUEST_URI'] = '/';
define('WP_INSTALLING', $accion === 'instalar');
require $dir . '/wp-load.php';
add_filter('pre_wp_mail', '__return_false');

if ($accion === 'instalar') {
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    wp_install('Plugins e imágenes', 'admin', 'admin@example.com', true, '', wp_generate_password(24));
    update_option('siteurl', $url);
    update_option('home', $url);
    $id = wp_insert_post([
        'post_title'   => 'Optimizar imágenes en el servidor',
        'post_excerpt' => 'Cómo convertir a WebP y AVIF desde PHP sin plugins.',
        'post_content' => 'Contenido del post de prueba.',
        'post_status'  => 'publish',
    ]);
    echo "ok $id\n";
}

if ($accion === 'activar') {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    deactivate_plugins(get_option('active_plugins', []), true);
    // Rank Math no imprime nada en el front-end hasta conectar una cuenta suya o
    // pulsar «saltar» en el asistente, que guarda esta opción. Hacemos lo segundo.
    update_option('rank_math_registration_skip', true);
    foreach ($args as $plugin) {
        $r = activate_plugin($plugin);
        if (is_wp_error($r)) {
            exit("error $plugin: " . $r->get_error_message() . "\n");
        }
    }
    echo 'ok ' . implode(',', get_option('active_plugins', [])) . "\n";
}

if ($accion === 'imagen') {
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    $tmp = wp_tempnam();
    copy($args[0], $tmp);
    $id = media_handle_sideload(['name' => basename($args[0]), 'tmp_name' => $tmp], 0);
    if (is_wp_error($id)) {
        exit('error ' . $id->get_error_message() . "\n");
    }
    $meta = wp_get_attachment_metadata($id);
    $carpeta = dirname(get_attached_file($id));
    echo "ok\n";
    printf("  %-34s %s\n", basename($meta['file']), round(filesize(get_attached_file($id)) / 1024, 1) . ' KB (subida)');
    foreach ($meta['sizes'] as $tamano => $copia) {
        printf("  %-34s %s KB  [%s]\n", $copia['file'], round(filesize("$carpeta/{$copia['file']}") / 1024, 1), $tamano);
    }
}
