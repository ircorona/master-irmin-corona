<?php
/**
 * Plugin Name: Formato WebP
 * Description: Las imágenes JPEG y PNG que se suben se guardan como WebP (todas sus copias redimensionadas). Es un mu-plugin: va en wp-content/mu-plugins/.
 */

// Filtro del núcleo (WordPress 5.8+ para WebP, 6.5+ para AVIF)
add_filter('image_editor_output_format', function (array $formatos): array {
    $formatos['image/jpeg'] = 'image/webp';
    $formatos['image/png']  = 'image/webp';
    return $formatos;
});

// WordPress guarda WebP a calidad 86 y JPEG a 82: sin esto, el WebP puede pesar MÁS
// que el JPEG (medido: 174,9 KB frente a 160,3 KB en la imagen del ejercicio).
add_filter('wp_editor_set_quality', function (int $calidad, string $mime): int {
    return $mime === 'image/webp' ? 75 : $calidad;
}, 10, 2);
