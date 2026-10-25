<?php
// Compara el HTML que manda el servidor (lo que ve un bot sin JS) con el DOM
// que deja Chrome headless tras ejecutar el JS (lo que ve Google al renderizar).
// Uso: php prueba.php [ruta-a-chrome]

$chrome = $argv[1] ?? 'C:/Program Files/Google/Chrome/Application/chrome.exe';
$puerto = 8096;
$base   = "http://127.0.0.1:$puerto";

$servidor = proc_open(
    [PHP_BINARY, '-S', "127.0.0.1:$puerto", '-t', __DIR__, __DIR__ . '/router.php'],
    [1 => ['file', 'NUL', 'w'], 2 => ['file', 'NUL', 'w']],
    $tuberias
);
usleep(500000);

function codigo(string $url): int
{
    @file_get_contents($url);
    return (int) substr(http_get_last_response_headers()[0] ?? '', 9, 3);
}

$html   = file_get_contents("$base/");
$perfil = sys_get_temp_dir() . '/chrome-jsseo-' . uniqid();
$dom    = shell_exec('"' . $chrome . '" --headless --disable-gpu --no-first-run'
        . ' --user-data-dir=' . escapeshellarg($perfil) . " --dump-dom $base/ 2>NUL");
$codigos = ['/productos/' => codigo("$base/productos/"), '/no-existe/' => codigo("$base/no-existe/")];
proc_terminate($servidor);

// ¿Hay un <a> con href (que no sea solo #) para este id?
function enlace(string $doc, string $id): string
{
    if (!preg_match('/<(\w+)[^>]*\bid="' . $id . '"[^>]*>/', $doc, $m)) {
        return 'ausente';
    }
    if ($m[1] !== 'a' || !preg_match('/\bhref="([^"]*)"/', $m[0], $h)) {
        return 'sin href';
    }
    return str_starts_with($h[1], '#') ? "href={$h[1]}" : 'enlace';
}

$casos = [
    'A' => ['<a href> + History API',  'enlace',   'enlace',   'sigue /productos/'],
    'B' => ['<a onclick> sin href',    'sin href', 'sin href', 'no sigue nada'],
    'C' => ['<span data-href>',        'sin href', 'sin href', 'no sigue nada'],
    'D' => ['<a href="#/blog">',       'href=#/blog', 'href=#/blog', 'ignora el #: misma URL'],
    'E' => ['<a href> insertado por JS', 'ausente', 'enlace',  'sigue tras renderizar'],
];

$fallos = 0;
printf("%-2s %-27s %-13s %-13s %s\n", '', 'Enlace', 'HTML (curl)', 'DOM (Chrome)', 'Google (documentado)');
foreach ($casos as $id => [$nombre, $esperaHtml, $esperaDom, $google]) {
    $h = enlace($html, $id);
    $d = enlace($dom, $id);
    $ok = ($h === $esperaHtml && $d === $esperaDom);
    $fallos += $ok ? 0 : 1;
    printf("%-2s %-27s %-13s %-13s %s%s\n", $id, $nombre, $h, $d, $google, $ok ? '' : '  INESPERADO');
}

// Las técnicas del artículo de Carlos
preg_match('/class="example-link"[^>]*>([^<]*)</', $dom, $texto);
preg_match('/data-rel-final="([^"]*)"/', $dom, $rel);
$anchor = $texto[1] ?? '?';
$relFin = $rel[1] ?? '?';
echo "\nCarlos, técnica 1 (anchor text): \"$anchor\"\n";
echo "Carlos, técnica 5 (nofollow -> noopener): rel final = \"$relFin\""
   . ($relFin === 'nofollow' ? "  (no cambió: newLink no existe, ReferenceError)\n" : "\n");
$fallos += ($anchor === 'Este es el nuevo Anchor Text' && $relFin === 'nofollow') ? 0 : 1;

echo "\nCódigos: /productos/ = {$codigos['/productos/']}, /no-existe/ = {$codigos['/no-existe/']}\n";
$fallos += ($codigos['/productos/'] === 200 && $codigos['/no-existe/'] === 404) ? 0 : 1;

$total = count($casos) + 2;
printf("\n%d/%d comprobaciones OK\n", $total - $fallos, $total);
exit($fallos ? 1 : 0);
