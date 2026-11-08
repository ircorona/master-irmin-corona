<?php
// Un cambio de PHP 8.0 que NO da error: la comparación no estricta entre número y cadena.
// En PHP 7, 0 == "a" convertía "a" a número (0) y daba true.
// En PHP 8, si la cadena no es numérica, se compara el número como cadena: "0" == "a" es false.
// La web no se rompe: simplemente hace otra cosa.

$pagina = $_GET['pagina'] ?? 'todas';   // un parámetro que llega como texto

// Lógica típica de una plantilla vieja: "si la página es 0, muestra la portada del listado"
if ($pagina == 0) {
    $resultado = 'portada del listado';
} else {
    $resultado = 'página ' . $pagina;
}

printf("PHP %s | 0 == \"todas\" -> %s | se muestra: %s\n", PHP_VERSION, var_export(0 == 'todas', true), $resultado);
