<?php
// Carga una de las dos configuraciones y devuelve en JSON qué constantes quedaron definidas.
// Por web: php -S ... y ?config=carlos|seguro. Por CLI: php ver-config.php carlos|seguro

$cual = PHP_SAPI === 'cli' ? ($argv[1] ?? 'carlos') : ($_GET['config'] ?? 'carlos');
$avisos = [];
set_error_handler(function ($no, $msg) use (&$avisos) {
    $avisos[] = $msg;
    return true;
});
require __DIR__ . ($cual === 'seguro' ? '/config-seguro.php' : '/config-carlos.php');
restore_error_handler();

$salida = ['host' => $_SERVER['HTTP_HOST'] ?? null, 'avisos' => $avisos];
foreach (['WP_ENVIRONMENT_TYPE', 'DB_NAME', 'DB_USER', 'WP_DEBUG', 'WP_DEBUG_DISPLAY', 'WP_DEBUG_LOG', 'WP_POST_REVISIONS'] as $c) {
    $salida[$c] = defined($c) ? constant($c) : null;
}
if (PHP_SAPI !== 'cli') {
    header('Content-Type: application/json');
}
echo json_encode($salida, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), "\n";
