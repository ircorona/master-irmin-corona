<?php
// Sirve las dos páginas con `php -S` y comprueba lo que recibe el navegador.
// Uso: php prueba.php

$puerto = 8095;
$servidor = proc_open(
    [PHP_BINARY, '-S', "127.0.0.1:$puerto", '-t', __DIR__],
    [1 => ['file', 'NUL', 'w'], 2 => ['file', 'NUL', 'w']],
    $tuberias
);
usleep(500000);

function pedir(int $puerto, string $fichero): array
{
    $html = file_get_contents("http://127.0.0.1:$puerto/$fichero");
    $tipo = '';
    // PHP 8.4+: $http_response_header está obsoleto en 8.5
    foreach (http_get_last_response_headers() ?? [] as $cabecera) {
        if (stripos($cabecera, 'Content-Type:') === 0) {
            $tipo = trim(substr($cabecera, 13));
        }
    }
    return [$html, $tipo];
}

[$clase, $tipoClase]       = pedir($puerto, 'clase.php');
[$mejorado, $tipoMejorado] = pedir($puerto, 'mejorado.php');
proc_terminate($servidor);

$comprobaciones = [
    'clase: el navegador no recibe código PHP'        => !str_contains($clase, '<?php') && !str_contains($clase, '$test'),
    'clase: charset solo por cabecera HTTP'           => stripos($tipoClase, 'charset=UTF-8') !== false && stripos($clase, '<meta charset') === false,
    'clase: hay espacio en blanco antes del DOCTYPE'  => !str_starts_with($clase, '<!DOCTYPE'),
    'clase: 6 posts, "Post 4" repetido'               => substr_count($clase, '<p>Post') === 6 && substr_count($clase, 'Post 4') === 2,
    'clase: 0 enlaces a los posts'                    => substr_count($clase, '<a ') === 0,
    'mejorado: empieza por el DOCTYPE'                => str_starts_with($mejorado, '<!DOCTYPE'),
    'mejorado: lang, charset y viewport'              => str_contains($mejorado, '<html lang="es">') && str_contains($mejorado, '<meta charset="utf-8">') && str_contains($mejorado, 'name="viewport"'),
    'mejorado: 5 enlaces <a href> a los posts'        => preg_match_all('#<li><a href="/blog/post-\d/">#', $mejorado) === 5,
];

$fallos = 0;
foreach ($comprobaciones as $nombre => $ok) {
    printf("%-50s %s\n", $nombre, $ok ? 'OK' : 'FALLO');
    $fallos += $ok ? 0 : 1;
}
printf("\n%d/%d comprobaciones OK\n", count($comprobaciones) - $fallos, count($comprobaciones));
exit($fallos ? 1 : 0);
