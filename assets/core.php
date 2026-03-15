<?php

// Obtener la URL solicitada
$request_uri = $_SERVER['REQUEST_URI'];

// Redirigir a la pagina correspondiente
if ($request_uri == '/robots') {
    echo "Pagina bloqueada por el robots.txt";
} elseif ($request_uri == '/error4xx') {
    header("HTTP/1.0 418 I'm a teapot");
    echo 'Soy una tetera';
} elseif ($request_uri == '/existir') {
    echo 'Esta pagina si existe';
} else {
    // Mostrar una pagina de error si la URL solicitada no coincide con ninguna pagina conocida
    header("HTTP/1.0 404 Not Found");
    include 'errores/404.php';
}
