<?php
/**
 * Busca en un proyecto PHP lo que PHP 7.0 y 8.0 eliminaron, con el tokenizador
 * (no cuenta lo que aparece dentro de comentarios ni de cadenas).
 *
 * Uso: php escanear.php <carpeta-o-fichero> [...]
 * Sale con 1 si encuentra algo.
 */

const ELIMINADAS = [
    // función => versión en la que se eliminó
    'each'                    => '8.0',
    'create_function'         => '8.0',
    'money_format'            => '8.0',
    'read_exif_data'          => '8.0',
    'get_magic_quotes_gpc'    => '8.0',
    'get_magic_quotes_runtime'=> '8.0',
    'ereg'                    => '7.0',
    'eregi'                   => '7.0',
    'ereg_replace'            => '7.0',
    'split'                   => '7.0',
    'mysql_connect'           => '7.0',
    'mysql_query'             => '7.0',
    'mysql_fetch_array'       => '7.0',
    'mysql_fetch_assoc'       => '7.0',
];

function ficheros(array $rutas): array
{
    $out = [];
    foreach ($rutas as $r) {
        if (is_file($r)) {
            $out[] = $r;
            continue;
        }
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($r, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if ($f->isFile() && strtolower($f->getExtension()) === 'php') {
                $out[] = $f->getPathname();
            }
        }
    }
    return $out;
}

if ($argc < 2) {
    fwrite(STDERR, "Uso: php escanear.php <carpeta-o-fichero> [...]\n");
    exit(2);
}

$hallazgos = 0;
$lista = ficheros(array_slice($argv, 1));
foreach ($lista as $fichero) {
    $tokens = token_get_all(file_get_contents($fichero));
    $n = count($tokens);
    for ($i = 0; $i < $n; $i++) {
        $t = $tokens[$i];
        if (!is_array($t)) {
            continue;
        }
        [$id, $texto, $linea] = $t;

        // Llamada a función eliminada: T_STRING seguido de "(" y no precedido de -> :: function
        if ($id === T_STRING && isset(ELIMINADAS[strtolower($texto)])) {
            $j = $i + 1;
            while ($j < $n && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
                $j++;
            }
            $k = $i - 1;
            while ($k >= 0 && is_array($tokens[$k]) && $tokens[$k][0] === T_WHITESPACE) {
                $k--;
            }
            $antes = $k >= 0 ? $tokens[$k] : null;
            $esMetodo = is_array($antes) && in_array($antes[0], [T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION], true);
            if ($j < $n && $tokens[$j] === '(' && !$esMetodo) {
                printf("%s:%d  %s()  eliminada en PHP %s\n", $fichero, $linea, $texto, ELIMINADAS[strtolower($texto)]);
                $hallazgos++;
            }
        }

        // Acceso con llaves a un offset: $cadena{0}. Eliminado en PHP 8.0.
        if ($id === T_VARIABLE) {
            $j = $i + 1;
            if ($j < $n && $tokens[$j] === '{') {
                printf("%s:%d  %s{...}  acceso con llaves eliminado en PHP 8.0 (usa [])\n", $fichero, $linea, $texto);
                $hallazgos++;
            }
        }
    }
}

printf("%d ficheros PHP revisados, %d hallazgos\n", count($lista), $hallazgos);
exit($hallazgos ? 1 : 0);
