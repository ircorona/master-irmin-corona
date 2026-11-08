<?php
/**
 * Compara dos fotos de foto-entorno.php: el servidor viejo frente al nuevo.
 *
 * Uso: php comparar.php entorno-viejo.json entorno-nuevo.json
 * Sale con 1 si hay algo que puede romper la web (salto de versión mayor o extensiones que faltan).
 */

if ($argc !== 3) {
    fwrite(STDERR, "Uso: php comparar.php viejo.json nuevo.json\n");
    exit(2);
}
$viejo = json_decode(file_get_contents($argv[1]), true);
$nuevo = json_decode(file_get_contents($argv[2]), true);
if (!is_array($viejo) || !is_array($nuevo)) {
    fwrite(STDERR, "Alguno de los dos ficheros no es JSON válido\n");
    exit(2);
}

$riesgo = false;
[$maV, $miV] = array_map('intval', explode('.', $viejo['php']));
[$maN, $miN] = array_map('intval', explode('.', $nuevo['php']));

echo "PHP: {$viejo['php']} ({$viejo['sapi']}) -> {$nuevo['php']} ({$nuevo['sapi']})\n";
if ($maV !== $maN) {
    echo "  ! Cambio de versión MAYOR ($maV -> $maN): hay funciones eliminadas y cambios de comportamiento. Revisa la guía de migración de php.net y pasa escanear.php\n";
    $riesgo = true;
} elseif ($miV !== $miN) {
    echo "  · Cambio de versión menor ($maV.$miV -> $maN.$miN): lee la guía de migración de esa versión\n";
}
if ($viejo['sapi'] !== $nuevo['sapi']) {
    echo "  · SAPI distinta: compara fotos tomadas igual (las dos por web o las dos por CLI)\n";
}

$faltan = array_values(array_diff($viejo['extensiones'], $nuevo['extensiones']));
$nuevas = array_values(array_diff($nuevo['extensiones'], $viejo['extensiones']));
if ($faltan) {
    echo "  ! Extensiones que había y ya no: " . implode(', ', $faltan) . "\n";
    $riesgo = true;
}
if ($nuevas) {
    echo "  · Extensiones nuevas: " . implode(', ', $nuevas) . "\n";
}

foreach ($viejo['ini'] as $clave => $v) {
    $n = $nuevo['ini'][$clave] ?? null;
    if ($v !== $n) {
        echo "  · ini $clave: " . var_export($v, true) . " -> " . var_export($n, true) . "\n";
    }
}

echo $riesgo ? "RIESGO: revisar antes de migrar\n" : "Sin diferencias que rompan\n";
exit($riesgo ? 1 : 0);
