<?php
// Compara las tres versiones de index.php:
//   1. el código en el servidor (con PHP)
//   2. el HTML que llega (lo que ve curl o «Ver código fuente»)
//   3. el DOM tras ejecutar el JS (Chrome headless --dump-dom)
// Uso: php comparar.php [url] [ruta-a-chrome]
// Sale con 1 si alguna comprobación falla.

$url    = $argv[1] ?? 'http://127.0.0.1:8090/';
$chrome = $argv[2] ?? 'C:/Program Files/Google/Chrome/Application/chrome.exe';

function parrafo(string $html, string $id): string
{
    return preg_match('#<p id="' . $id . '">(.*?)</p>#s', $html, $m) ? trim($m[1]) : '(no está)';
}

$fuente = file_get_contents(__DIR__ . '/index.php');
$http   = file_get_contents($url);
if ($http === false) {
    fwrite(STDERR, "No responde $url. Arranca antes: php -S 127.0.0.1:8090\n");
    exit(1);
}
$cmd = '"' . $chrome . '" --headless --disable-gpu --dump-dom ' . escapeshellarg($url) . ' 2>NUL';
$dom = (string) shell_exec($cmd);

$filas = [
    '1. servidor (index.php)' => $fuente,
    '2. HTML recibido'        => $http,
    '3. DOM renderizado'      => $dom,
];
foreach ($filas as $nombre => $html) {
    printf("%-24s #servidor: %-40s #test: %s\n",
        $nombre,
        // Laragon trae el CLI sin mbstring: nada de mb_*
        substr(preg_replace('/\s+/', ' ', parrafo($html, 'servidor')), 0, 40),
        parrafo($html, 'test') === '' ? '(vacío)' : parrafo($html, 'test'));
}

$checks = [
    'el HTML recibido no contiene código PHP'      => !str_contains($http, '<?php'),
    'el saludo del servidor ya viene en el HTML'   => preg_match('/¡Buen(os|as) (días|tardes|noches)!/u', parrafo($http, 'servidor')) === 1,
    '#test llega vacío en el HTML'                 => parrafo($http, 'test') === '',
    '#test solo tiene texto en el DOM renderizado' => str_starts_with(parrafo($dom, 'test'), 'JS: son las'),
];
$fallos = 0;
echo PHP_EOL;
foreach ($checks as $texto => $ok) {
    echo ($ok ? 'OK    ' : 'FALLO ') . $texto . PHP_EOL;
    $fallos += $ok ? 0 : 1;
}
echo PHP_EOL . (count($checks) - $fallos) . '/' . count($checks) . " comprobaciones correctas\n";
exit($fallos ? 1 : 0);
