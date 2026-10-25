<?php
// Cada texto de index.html vive en una capa distinta (HTML, CSS, JS). La prueba
// mide dónde aparece:
//   HTML      lo que manda el servidor (un bot sin JS)
//   DOM       el árbol tras ejecutar el JS (Google al renderizar)
//   innerText el texto que el propio navegador considera «texto de la página»
// La columna «En pantalla» no se mide: es lo que pinta el navegador.
// Uso: php prueba.php [ruta-a-chrome]

$chrome = $argv[1] ?? 'C:/Program Files/Google/Chrome/Application/chrome.exe';
$puerto = 8097;
$base   = "http://127.0.0.1:$puerto";

// Copia de index.html que, al final, guarda body.innerText en un atributo
$copia = __DIR__ . '/.medir.html';
file_put_contents($copia, str_replace('</body>',
    '<script>document.body.dataset.texto = document.body.innerText.replace(/\s+/g, " ");</script></body>',
    file_get_contents(__DIR__ . '/index.html')));

$servidor = proc_open([PHP_BINARY, '-S', "127.0.0.1:$puerto", '-t', __DIR__],
    [1 => ['file', 'NUL', 'w'], 2 => ['file', 'NUL', 'w']], $tuberias);
usleep(500000);

$html = file_get_contents("$base/index.html");
$dom  = shell_exec('"' . $chrome . '" --headless --disable-gpu --no-first-run --user-data-dir='
    . escapeshellarg(sys_get_temp_dir() . '/chrome-pilares-' . uniqid()) . " --dump-dom $base/.medir.html 2>NUL");
proc_terminate($servidor);
unlink($copia);

preg_match('/data-texto="([^"]*)"/', $dom, $m);
$innerText = $m[1] ?? '';
// Quitamos <style> y <script> para que no cuenten los textos escritos en el código
$limpiar = fn(string $s) => preg_replace('#<style.*?</style>|<script.*?</script>|data-texto="[^"]*"#s', '', $s);
$html = $limpiar($html);
$dom  = $limpiar($dom);

//                      texto                 nombre                    HTML   DOM    innerText  en pantalla
$casos = [
    ['TEXTO-H1',           '<h1>',                     true,  true,  true,  'sí'],
    ['TEXTO-SPAN-GRANDE',  '<span> con aspecto de h1', true,  true,  true,  'sí, como un h1'],
    ['TEXTO-DISPLAY-NONE', 'display:none',             true,  true,  false, 'no'],
    ['TEXTO-CSS-BEFORE',   '::before { content }',     false, false, false, 'sí'],
    ['TEXTO-ALT',          'alt de <img>',             true,  true,  false, 'no (solo si falla)'],
    ['TEXTO-JS',           'insertado por JS',         false, true,  true,  'sí'],
];

$si = fn(bool $b) => $b ? 'x' : '-';
printf("%-26s %-6s %-6s %-10s %s\n", 'Texto', 'HTML', 'DOM', 'innerText', 'En pantalla');
$fallos = 0;
foreach ($casos as [$texto, $nombre, $eHtml, $eDom, $eTxt, $pantalla]) {
    $medido = [str_contains($html, $texto), str_contains($dom, $texto), str_contains($innerText, $texto)];
    $ok = $medido === [$eHtml, $eDom, $eTxt];
    $fallos += $ok ? 0 : 1;
    printf("%-26s %-6s %-6s %-10s %s%s\n", $nombre, ...[...array_map($si, $medido), $pantalla, $ok ? '' : '  INESPERADO']);
}

$h1  = preg_match_all('/<h1[\s>]/', $html);
$img = preg_match_all('/<img\s/', $html);
printf("\n<h1> en el HTML: %d (el span grande no cuenta)\n", $h1);
printf("<img> en el HTML: %d (el fondo CSS no es una imagen de la página)\n", $img);
$fallos += ($h1 === 1 && $img === 1) ? 0 : 1;

printf("\n%d/%d comprobaciones OK\n", count($casos) + 1 - $fallos, count($casos) + 1);
exit($fallos ? 1 : 0);
