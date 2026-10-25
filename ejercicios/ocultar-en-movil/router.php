<?php
// Router para `php -S`: apunta cada petición en peticiones.log y sirve
// un PNG de 1x1 para cualquier /img/*.png, sin necesidad de ficheros reales.

$ruta = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
file_put_contents(__DIR__ . '/peticiones.log', $ruta . "\n", FILE_APPEND | LOCK_EX);

if (preg_match('#^/img/[\w-]+\.png$#', $ruta)) {
    header('Content-Type: image/png');
    header('Cache-Control: no-store');
    echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
    return true;
}
return false; // el resto (index.html) lo sirve el servidor integrado
