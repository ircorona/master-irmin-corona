<?php
/**
 * Prueba el condicional de base de datos de la clase frente a la versión mejorada.
 * Uso: php prueba.php
 * Cada caso corre en un proceso aparte (define() no se puede repetir en el mismo).
 */

const PROD = 'nombre_ddbbproduccion';
const LOCAL = 'nombreddbb_local';

// host (null = línea de comandos), qué es, esperado en la clase, esperado en la mejorada
$casos = [
    ['dominioproduccion.com', 'producción, como en clase', PROD, PROD],
    ['www.dominioproduccion.com', 'producción con www', LOCAL, PROD],
    ['DominioProduccion.com', 'mayúsculas (un script, no un navegador)', LOCAL, PROD],
    ['dominioproduccion.com:8443', 'puerto no estándar', LOCAL, PROD],
    ['dominionuevo.com', 'el dominio nuevo, sin añadir a la clase', LOCAL, PROD],
    ['localhost', 'local', LOCAL, LOCAL],
    [null, 'línea de comandos (WP-CLI, cron)', LOCAL, LOCAL],
];

function base(string $config, ?string $host): array
{
    $cmd = escapeshellarg(PHP_BINARY) . ' -d display_errors=stderr ' . escapeshellarg(__DIR__ . '/ver.php')
        . ' ' . escapeshellarg(__DIR__ . "/$config") . ($host === null ? '' : ' ' . escapeshellarg($host));
    $p = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $tuberias);
    $salida = trim(stream_get_contents($tuberias[1]));
    $aviso = trim(stream_get_contents($tuberias[2]));
    proc_close($p);
    return [$salida, $aviso !== ''];
}

echo 'PHP ' . PHP_VERSION . "\n\n";
printf("%-28s %-12s %-12s %s\n", 'Host', 'Clase', 'Mejorada', 'Resultado');
$bien = 0;
$avisos = 0;
foreach ($casos as [$host, $que, $espClase, $espMejor]) {
    [$c, $avisoC] = base('config-clase.php', $host);
    [$m, $avisoM] = base('config-mejorada.php', $host);
    $ok = $c === $espClase && $m === $espMejor && !$avisoM;
    $bien += $ok ? 1 : 0;
    $avisos += $avisoC ? 1 : 0;
    $corto = fn($b) => $b === PROD ? 'producción' : ($b === LOCAL ? 'local' : "?$b");
    // Sin mbstring (el PHP 8.5 de Laragon no lo trae): contar caracteres UTF-8 con preg_match_all.
    $col = fn(string $s, int $n) => $s . str_repeat(' ', max(1, $n - preg_match_all('/./u', $s)));
    echo $col($host ?? '(sin Host)', 29), $col($corto($c) . ($avisoC ? '*' : ''), 13),
        $col($corto($m), 13), $ok ? 'correcto' : 'FALLO', "  ($que)\n";
}
echo "\n* = la clase da un aviso de PHP (Undefined array key \"HTTP_HOST\")\n";
echo "Casos correctos: $bien de " . count($casos) . "\n";
exit($bien === count($casos) && $avisos === 1 ? 0 : 1);
