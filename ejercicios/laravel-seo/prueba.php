<?php
// Monta Laravel 13 + laravel/head en la carpeta temporal (fuera de OneDrive), copia
// las rutas y vistas de este ejercicio y mide códigos, title, canonical y robots.
// Uso (PHP 8.3 de Laragon: Laravel 13 pide PHP 8.3+):
//   php prueba.php        (la primera vez instala Laravel: unos 5 minutos)

$php      = PHP_BINARY;
$composer = 'C:/laragon/bin/composer/composer.phar';
$base     = sys_get_temp_dir();
$proyecto = "$base/laravel-seo";
$puerto   = 8100;

if (!is_dir("$proyecto/vendor/laravel/head")) {
    echo "Instalando Laravel 13 y laravel/head en $proyecto ...\n";
    passthru(escapeshellarg($php) . " $composer create-project laravel/laravel " . escapeshellarg($proyecto) . ' "13.*" --no-interaction --quiet');
    passthru('cd ' . escapeshellarg($proyecto) . ' && ' . escapeshellarg($php) . " $composer require laravel/head --no-interaction --quiet");
}

// Copia los ficheros propios encima del esqueleto
$copiar = function (string $origen, string $destino) use (&$copiar) {
    foreach (scandir($origen) as $f) {
        if ($f === '.' || $f === '..') continue;
        if (is_dir("$origen/$f")) {
            @mkdir("$destino/$f", 0777, true);
            $copiar("$origen/$f", "$destino/$f");
        } else {
            copy("$origen/$f", "$destino/$f");
        }
    }
};
$copiar(__DIR__ . '/routes', "$proyecto/routes");
$copiar(__DIR__ . '/resources', "$proyecto/resources");
passthru('cd ' . escapeshellarg($proyecto) . ' && ' . escapeshellarg($php) . ' artisan view:clear --quiet');

// Lo mismo que `php artisan serve`, sin el proceso hijo: servidor integrado + router de Laravel
$servidor = proc_open(
    [$php, '-S', "127.0.0.1:$puerto", '-t', "$proyecto/public", "$proyecto/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php"],
    [1 => ['file', 'NUL', 'w'], 2 => ['file', 'NUL', 'w']], $tuberias, "$proyecto/public" // server.php busca index.php en el directorio actual
);
usleep(800000);

function pedir(string $url): array
{
    $ctx = stream_context_create(['http' => ['follow_location' => 0, 'ignore_errors' => true]]);
    $html = (string) file_get_contents($url, false, $ctx);
    // PHP 8.4+ tiene http_get_last_response_headers(); este script corre en 8.3
    $cab = function_exists('http_get_last_response_headers')
        ? (http_get_last_response_headers() ?? [])
        : ($http_response_header ?? []);
    $loc = '';
    foreach ($cab as $c) {
        if (stripos($c, 'Location:') === 0) $loc = trim(substr($c, 9));
    }
    $head = explode('</head>', $html)[0];
    preg_match('/<title>([^<]*)<\/title>/', $head, $t);
    preg_match('/<link rel="canonical" href="([^"]*)"/', $head, $c);
    preg_match('/<meta name="robots" content="([^"]*)"/', $head, $r);
    return [
        'codigo'    => (int) substr($cab[0] ?? '', 9, 3),
        'location'  => $loc,
        'title'     => html_entity_decode($t[1] ?? ''),
        'canonical' => $c[1] ?? '',
        'robots'    => $r[1] ?? '',
        'json-ld'   => str_contains($head, 'application/ld+json'),
    ];
}

$rutas = ['/producto', '/producto/', '/producto?utm_source=x', '/viejo', '/antiguo', '/admin', '/no-existe'];
$r = [];
printf("%-24s %-5s %-30s %-34s %s\n", 'Ruta', 'Cód.', 'title / Location', 'canonical', 'robots');
foreach ($rutas as $ruta) {
    $r[$ruta] = $x = pedir("http://127.0.0.1:$puerto$ruta");
    printf("%-24s %-5d %-30s %-34s %s\n", $ruta, $x['codigo'], $x['location'] ?: $x['title'], $x['canonical'], $x['robots']);
}
proc_terminate($servidor);

$htaccess = file_get_contents("$proyecto/public/.htaccess");
$reglaBarra = str_contains($htaccess, 'Redirect Trailing Slashes');
echo "\npublic/.htaccess trae la regla «Redirect Trailing Slashes»: " . ($reglaBarra ? 'sí (solo Apache/LiteSpeed)' : 'no') . "\n";
preg_match("/Laravel Framework ([\d.]+)/", (string) shell_exec(escapeshellarg($php) . ' ' . escapeshellarg("$proyecto/artisan") . ' --version --no-ansi'), $v);
echo 'Laravel ' . ($v[1] ?? '?') . "\n\n";

// Laravel Head normaliza el canonical a https por defecto, aunque el servidor sea http
$canon = 'https://127.0.0.1:' . $puerto . '/producto';
$checks = [
    '/producto: title, canonical propio y JSON-LD con @push' => $r['/producto']['title'] === 'Zapatillas de trail | Tienda' && $r['/producto']['canonical'] === $canon && $r['/producto']['json-ld'],
    '/producto/ sin .htaccess: 200 duplicado, mismo canonical' => $r['/producto/']['codigo'] === 200 && $r['/producto/']['canonical'] === $canon,
    '?utm_source: el canonical quita el parámetro' => $r['/producto?utm_source=x']['canonical'] === $canon,
    'Route::redirect: 302 por defecto' => $r['/viejo']['codigo'] === 302,
    'Route::permanentRedirect: 301' => $r['/antiguo']['codigo'] === 301,
    'withHead(robots:): noindex en /admin' => str_contains($r['/admin']['robots'], 'noindex'),
    'Ruta inexistente: 404 con la vista errors/404' => $r['/no-existe']['codigo'] === 404,
    'El .htaccess del esqueleto redirige la barra final' => $reglaBarra,
];
$fallos = 0;
foreach ($checks as $nombre => $ok) {
    printf("%-58s %s\n", $nombre, $ok ? 'OK' : 'FALLO');
    $fallos += $ok ? 0 : 1;
}
printf("\n%d/%d comprobaciones OK\n", count($checks) - $fallos, count($checks));
exit($fallos ? 1 : 0);
