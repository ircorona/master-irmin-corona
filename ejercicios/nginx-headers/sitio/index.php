<?php
// Un "WordPress" mínimo: todas las URLs bonitas acaban aquí por try_files,
// como en un WordPress real. Envía su propia cabecera Link, la misma que
// manda WordPress para la API REST, para ver qué pasa al sumar otra desde nginx.
header('Link: <https://mi-web.test/wp-json/>; rel="https://api.w.org/"');
header('Content-Type: text/html; charset=UTF-8');
// Las páginas que existen; el resto, 404 como en WordPress.
$ruta = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/') . '/';
if (!in_array($ruta, ['/', '/sobre-mi/', '/servicios-seo/', '/servicios-seo-locales/', '/guia-seo/'], true)) {
    http_response_code(404);
}
$uri = htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8');
echo "<!doctype html><title>WordPress de prueba</title><p>WordPress sirve: {$uri}</p>\n";
