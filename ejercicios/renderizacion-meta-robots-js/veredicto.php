<?php
// Para cada página: meta robots en el HTML recibido y en el DOM renderizado,
// y lo que haría con ella un bot sin JS y Google según su documentación
// («When Google encounters the noindex tag, it may skip rendering»).
// Uso: php veredicto.php [base-url] [ruta-a-chrome]
// Sale con 1 si algún resultado no es el esperado.

$base   = rtrim($argv[1] ?? 'http://127.0.0.1:8091', '/');
$chrome = $argv[2] ?? 'C:/Program Files/Google/Chrome/Application/chrome.exe';

function robots(string $html): string
{
    return preg_match('#<meta name="robots" content="([^"]*)"#i', $html, $m) ? $m[1] : '(sin meta)';
}

function bloquea(string $valor): bool
{
    return (bool) preg_match('/\b(noindex|none)\b/i', $valor);
}

// página => lo que Google acaba aplicando
$paginas = [
    'noindex-a-index.html' => 'noindex',
    'index-a-noindex.html' => 'noindex',
];

$fallos = 0;
foreach ($paginas as $pagina => $esperado) {
    $url  = "$base/$pagina";
    $http = @file_get_contents($url);
    if ($http === false) {
        fwrite(STDERR, "No responde $url. Arranca antes: php -S 127.0.0.1:8091\n");
        exit(1);
    }
    $dom = (string) shell_exec('"' . $chrome . '" --headless --disable-gpu --dump-dom ' . escapeshellarg($url) . ' 2>NUL');

    $fuente    = robots($http);
    $render    = robots($dom);
    $sinJs     = bloquea($fuente) ? 'noindex' : 'index';
    // Si la fuente ya bloquea, Google puede no renderizar: se queda con la fuente.
    $google    = bloquea($fuente) ? 'noindex' : (bloquea($render) ? 'noindex' : 'index');
    $ok        = $google === $esperado;
    $fallos   += $ok ? 0 : 1;

    echo $pagina . PHP_EOL;
    printf("  HTML recibido : %s\n", $fuente);
    printf("  DOM renderizado: %s\n", $render);
    printf("  bot sin JS     : %s\n", $sinJs);
    printf("  Google         : %s%s\n", $google,
        bloquea($fuente) ? '  (puede no renderizar: el cambio a index no llega a verse)' : '  (renderiza y aplica el DOM)');
    echo ($ok ? '  OK' : '  FALLO') . PHP_EOL . PHP_EOL;
}

echo (count($paginas) - $fallos) . '/' . count($paginas) . " páginas con el resultado esperado\n";
exit($fallos ? 1 : 0);
