<?php
// Router para `php -S`. Una SPA necesita que el servidor devuelva la página en
// cada ruta que existe (si no, al recargar /productos/ sale un 404)... pero SOLO en
// las que existen: un comodín que devuelve 200 a todo es un soft 404.

$rutas = ['/', '/productos/', '/servicios/', '/contacto/', '/insertado/'];
$ruta  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (in_array($ruta, $rutas, true)) {
    header('Content-Type: text/html; charset=utf-8');
    readfile(__DIR__ . '/index.html');
    return true;
}

http_response_code(404);
header('Content-Type: text/html; charset=utf-8');
echo '<!doctype html><title>No encontrada</title><h1>404</h1>';
return true;
