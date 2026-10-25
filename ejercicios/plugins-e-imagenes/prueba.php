<?php
// Monta un WordPress con SQLite en una carpeta temporal y mide:
//  1. qué etiquetas SEO salen con ninguno, uno o dos plugins de SEO
//  2. qué ficheros genera WordPress al subir una imagen, sin y con el filtro a WebP
// Uso (PHP 8.3 de Laragon: trae GD con WebP/AVIF y pdo_sqlite):
//   php prueba.php
// La primera vez descarga WordPress, SQLite, Yoast y Rank Math a .cache/ (fuera de git).

$php    = PHP_BINARY;
$puerto = 8089;
$url    = "http://127.0.0.1:$puerto";
$cache  = __DIR__ . '/.cache';
$imagen = realpath(__DIR__ . '/../../images/how_do_you_turn_this_on.jpg');

$descargas = [
    'wordpress.zip' => 'https://wordpress.org/latest.zip',
    'sqlite.zip'    => 'https://downloads.wordpress.org/plugin/sqlite-database-integration.zip',
    'yoast.zip'     => 'https://downloads.wordpress.org/plugin/wordpress-seo.zip',
    'rankmath.zip'  => 'https://downloads.wordpress.org/plugin/seo-by-rank-math.zip',
];

@mkdir($cache);
foreach ($descargas as $nombre => $origen) {
    $reutilizable = __DIR__ . "/../indirecciones/.cache/$nombre";
    if (!file_exists("$cache/$nombre") && file_exists($reutilizable)) {
        copy($reutilizable, "$cache/$nombre");
    }
    if (!file_exists("$cache/$nombre")) {
        echo "descargando $origen\n";
        file_put_contents("$cache/$nombre", file_get_contents($origen));
    }
}

// --- WordPress en una carpeta temporal ---
$tmp = sys_get_temp_dir() . '/wp-plugins-' . getmypid();
$wp  = "$tmp/wordpress";
@mkdir($tmp);
// El tar de Windows (bsdtar) abre .zip. El de Git Bash (GNU) lee "C:" como un host remoto.
$tar = fn($zip, $dest) => shell_exec('C:\\Windows\\System32\\tar.exe -xf ' . escapeshellarg($zip) . ' -C ' . escapeshellarg($dest));
$tar("$cache/wordpress.zip", $tmp);
foreach (['sqlite.zip', 'yoast.zip', 'rankmath.zip'] as $zip) {
    $tar("$cache/$zip", "$wp/wp-content/plugins");
}
copy("$wp/wp-content/plugins/sqlite-database-integration/db.copy", "$wp/wp-content/db.php");
@mkdir("$wp/wp-content/database");
@mkdir("$wp/wp-content/mu-plugins");
@mkdir("$wp/wp-content/plugins/mi-seo");
copy(__DIR__ . '/mi-seo/mi-seo.php', "$wp/wp-content/plugins/mi-seo/mi-seo.php");
file_put_contents("$wp/wp-config.php", <<<'PHP'
<?php
define('DB_NAME', 'wp'); define('DB_USER', 'x'); define('DB_PASSWORD', 'x'); define('DB_HOST', 'localhost');
define('DB_DIR', __DIR__ . '/wp-content/database/'); define('DB_FILE', 'wp.sqlite');
define('DISABLE_WP_CRON', true); define('WP_DEBUG', false);
$table_prefix = 'wp_';
if (!defined('ABSPATH')) define('ABSPATH', __DIR__ . '/');
require_once ABSPATH . 'wp-settings.php';
PHP);

function wp(string ...$args): string
{
    global $php, $wp, $url;
    $cmd = escapeshellarg($php) . ' ' . escapeshellarg(__DIR__ . '/wp.php') . ' '
         . implode(' ', array_map('escapeshellarg', [$wp, $url, ...$args])) . ' 2>&1';
    $salida = (string) shell_exec($cmd);
    if (!str_starts_with($salida, 'ok')) {
        fwrite(STDERR, "wp.php falló: $salida\n");
        exit(1); // exit("texto") saldría con 0
    }
    return $salida;
}

$postId = (int) substr(trim(wp('instalar')), 3);
$version = preg_match("/wp_version = '([^']+)'/", file_get_contents("$wp/wp-includes/version.php"), $m) ? $m[1] : '?';
echo "WordPress $version instalado, post de prueba ?p=$postId\n\n";

$servidor = proc_open([$php, '-S', "127.0.0.1:$puerto", '-t', $wp],
    [1 => ['file', 'NUL', 'w'], 2 => ['file', 'NUL', 'w']], $tuberias);
usleep(700000);

// --- 1. Plugins de SEO ---
$yoast = 'wordpress-seo/wp-seo.php';
$rank  = 'seo-by-rank-math/rank-math.php';
$mio   = 'mi-seo/mi-seo.php';
$escenarios = [
    'Ningún plugin'      => [],
    'Mi SEO'             => [$mio],
    'Yoast'              => [$yoast],
    'Rank Math'          => [$rank],
    'Yoast + Rank Math'  => [$yoast, $rank],
    'Mi SEO + Yoast'     => [$mio, $yoast],
];
$patrones = [
    'title'     => '/<title[\s>]/i',
    'canonical' => '/<link[^>]+rel=["\']canonical["\']/i',
    'descr.'    => '/<meta[^>]+name=["\']description["\']/i',
    'robots'    => '/<meta[^>]+name=["\']robots["\']/i',
    'og:title'  => '/<meta[^>]+property=["\']og:title["\']/i',
    'JSON-LD'   => '/<script[^>]+application\/ld\+json/i',
];

printf("%-19s", 'Plugins');
foreach (array_keys($patrones) as $p) printf("%10s", $p);
printf("%9s\n", 'KB HTML');

$resultados = [];
foreach ($escenarios as $nombre => $plugins) {
    wp('activar', ...$plugins);
    $html = (string) @file_get_contents("$url/?p=$postId");
    printf("%-19s", $nombre);
    foreach ($patrones as $clave => $patron) {
        $resultados[$nombre][$clave] = preg_match_all($patron, $html);
        printf("%10d", $resultados[$nombre][$clave]);
    }
    printf("%9.1f\n", strlen($html) / 1024);
}

// --- 2. Imágenes ---
wp('activar');
echo "\nSubida de {$imagen} sin filtro:\n";
echo substr(wp('imagen', $imagen), 3);
copy(__DIR__ . '/formato-webp.php', "$wp/wp-content/mu-plugins/formato-webp.php");
echo "\nSubida con el mu-plugin formato-webp.php:\n";
$conFiltro = substr(wp('imagen', $imagen), 3);
echo $conFiltro;

proc_terminate($servidor);

// --- Comprobaciones ---
$r = $resultados;
$checks = [
    'Sin plugins: WordPress ya pone 1 canonical'         => $r['Ningún plugin']['canonical'] === 1 && $r['Ningún plugin']['descr.'] === 0,
    'Mi SEO: 1 description, 1 JSON-LD, 1 canonical'      => $r['Mi SEO']['descr.'] === 1 && $r['Mi SEO']['JSON-LD'] === 1 && $r['Mi SEO']['canonical'] === 1,
    'Yoast solo: nada duplicado'                         => max($r['Yoast']) === 1,
    'Rank Math solo: nada duplicado'                     => max($r['Rank Math']) === 1,
    'Yoast + Rank Math: alguna etiqueta duplicada'       => max($r['Yoast + Rank Math']) > 1,
    'Mi SEO + Yoast: igual que Yoast solo'               => $r['Mi SEO + Yoast'] === $r['Yoast'],
    'Filtro WebP: las copias salen en .webp'             => substr_count($conFiltro, '.webp') >= 3,
    'Filtro WebP a q75: la subida pesa menos que el JPEG' => (float) preg_replace('/.*?([\d.]+) KB \(subida\).*/s', '$1', $conFiltro) < filesize($imagen) / 1024,
];
echo "\n";
$fallos = 0;
foreach ($checks as $nombre => $ok) {
    printf("%-52s %s\n", $nombre, $ok ? 'OK' : 'FALLO');
    $fallos += $ok ? 0 : 1;
}
printf("\n%d/%d comprobaciones OK\n", count($checks) - $fallos, count($checks));
echo "WordPress de prueba en: $wp\n";
exit($fallos ? 1 : 0);
