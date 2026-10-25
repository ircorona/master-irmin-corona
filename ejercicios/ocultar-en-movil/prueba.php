<?php
// Carga index.html con Chrome headless a ancho de móvil y de escritorio
// y mira en el log del servidor qué imágenes se pidieron de verdad.
// Uso: php prueba.php [ruta-a-chrome]

$chrome = $argv[1] ?? 'C:/Program Files/Google/Chrome/Application/chrome.exe';
$php    = PHP_BINARY;
$puerto = 8093;
$log    = __DIR__ . '/peticiones.log';

$servidor = proc_open(
    [$php, '-S', "127.0.0.1:$puerto", '-t', __DIR__, __DIR__ . '/router.php'],
    [1 => ['file', 'NUL', 'w'], 2 => ['file', 'NUL', 'w']],
    $tuberias
);
usleep(500000);

function cargar(string $chrome, int $puerto, string $ventana, string $log): array
{
    @unlink($log);
    $perfil = sys_get_temp_dir() . '/chrome-ocultar-' . uniqid();
    $cmd = '"' . $chrome . '" --headless --disable-gpu --no-first-run'
         . ' --user-data-dir=' . escapeshellarg($perfil)
         . " --window-size=$ventana --virtual-time-budget=3000 --dump-dom"
         . " http://127.0.0.1:$puerto/index.html 2>NUL";
    shell_exec($cmd);
    $pedidas = file_exists($log) ? file($log, FILE_IGNORE_NEW_LINES) : [];
    return array_values(array_unique(array_filter($pedidas, fn($r) => str_starts_with($r, '/img/'))));
}

// Lo esperado es lo que hace Chrome HEADLESS. B y E salen pedidas porque headless
// ignora loading="lazy" (E, a 9000px, lo demuestra). En un Chrome normal ninguna de
// las dos se pide al cargar: ver README, «Comprobar B en un navegador real».
$casos = [
    'img/a-oculta.png'      => ['A <img> display:none',      true,  true],
    'img/b-oculta-lazy.png' => ['B <img> display:none lazy', true,  true],
    'img/c-movil.png'       => ['C <picture> movil',         true,  false],
    'img/c-escritorio.png'  => ['C <picture> escritorio',    false, true],
    'img/d-fondo.png'       => ['D fondo CSS escritorio',    false, true],
    'img/e-lejos-lazy.png'  => ['E control lazy a 9000px',   true,  true],
];

$movil      = cargar($chrome, $puerto, '390,844', $log);
$escritorio = cargar($chrome, $puerto, '1280,800', $log);
proc_terminate($servidor);
@unlink($log);

$fallos = 0;
printf("%-28s %-8s %-10s\n", 'Imagen', 'Movil', 'Escritorio');
foreach ($casos as $fichero => [$nombre, $esperaMovil, $esperaEscritorio]) {
    $m = in_array("/$fichero", $movil, true);
    $e = in_array("/$fichero", $escritorio, true);
    $ok = ($m === $esperaMovil && $e === $esperaEscritorio);
    $fallos += $ok ? 0 : 1;
    printf("%-28s %-8s %-10s %s\n", $nombre, $m ? 'pedida' : '-', $e ? 'pedida' : '-', $ok ? 'OK' : 'INESPERADO');
}

$html = file_get_contents(__DIR__ . '/index.html');
preg_match_all('/<a\s[^>]*href="([^"]+)"/', $html, $m);
printf("\nEnlaces en el HTML: %d (%d URLs distintas)\n", count($m[1]), count(array_unique($m[1])));

printf("%d/%d casos con el resultado esperado\n", count($casos) - $fallos, count($casos));
exit($fallos ? 1 : 0);
