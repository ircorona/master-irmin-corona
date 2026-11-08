<?php
// Uso: php ver.php <config.php> [host]
// Simula una petición con ese Host (sin host = línea de comandos) y dice qué base de datos elige.
if ($argc < 2) {
    fwrite(STDERR, "Uso: php ver.php <config.php> [host]\n");
    exit(2);
}
if (isset($argv[2])) {
    $_SERVER['HTTP_HOST'] = $argv[2];
}
require $argv[1];
echo DB_NAME, "\n";
