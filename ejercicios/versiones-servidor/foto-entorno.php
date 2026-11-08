<?php
/**
 * Foto del entorno PHP para archivar antes de una migración.
 *
 * Uso:
 *   php foto-entorno.php > entorno-viejo.json          (CLI)
 *   https://servidor-viejo/foto-entorno.php             (web: JSON)
 *   https://servidor-viejo/foto-entorno.php?html=1      (web: phpinfo() sin datos sensibles)
 *
 * Ojo: la foto que vale es la del SERVIDOR WEB, no la de la línea de comandos.
 * Pueden usar php.ini distintos. Borra este fichero del servidor en cuanto lo uses.
 */

// phpinfo() con INFO_GENERAL (1) + INFO_CONFIGURATION (4) + INFO_MODULES (8) = 13.
// Deja fuera INFO_ENVIRONMENT (16) e INFO_VARIABLES (32): variables de entorno,
// cookies y cabeceras, donde puede haber contraseñas o sesiones.
if (PHP_SAPI !== 'cli' && isset($_GET['html'])) {
    phpinfo(INFO_GENERAL | INFO_CONFIGURATION | INFO_MODULES);
    exit;
}

$ini = [
    'memory_limit', 'max_execution_time', 'max_input_vars', 'upload_max_filesize',
    'post_max_size', 'display_errors', 'error_reporting', 'date.timezone',
    'default_charset', 'short_open_tag', 'allow_url_fopen', 'output_buffering',
];
$valores = [];
foreach ($ini as $clave) {
    $v = ini_get($clave);
    $valores[$clave] = ($v === false) ? null : $v;
}

$extensiones = array_map('strtolower', get_loaded_extensions());
sort($extensiones);

$foto = [
    'tomada'      => gmdate('c'),
    'php'         => PHP_VERSION,
    'sapi'        => PHP_SAPI,
    'so'          => PHP_OS_FAMILY . ' ' . php_uname('r'),
    'php_ini'     => php_ini_loaded_file() ?: null,
    'servidor'    => $_SERVER['SERVER_SOFTWARE'] ?? null,
    'extensiones' => $extensiones,
    'ini'         => $valores,
];

if (PHP_SAPI !== 'cli') {
    header('Content-Type: application/json; charset=utf-8');
}
echo json_encode($foto, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), "\n";
