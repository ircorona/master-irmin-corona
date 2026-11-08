<?php
/**
 * Prueba del ejercicio. Uso: php prueba.php
 * Necesita curl.exe (viene con Windows 10/11) para mandar cabeceras Host a mano.
 */

require __DIR__ . '/reemplazo.php';

$ok = 0;
$total = 0;
function check(string $nombre, bool $cond, string $detalle = ''): void
{
    global $ok, $total;
    $total++;
    $ok += $cond ? 1 : 0;
    echo ($cond ? '  [OK] ' : '  [FALLO] ') . $nombre . ($detalle !== '' ? "  -> $detalle" : '') . "\n";
}

// Tres valores típicos de una base de datos de WordPress, con el dominio local de la clase.
$local = 'sanchezdonate.test';
$opcion = serialize(['titulo' => 'Contacto', 'url' => "https://$local/contacto/", 'columnas' => 3]);      // wp_options (widget)
$elementor = json_encode([['settings' => ['link' => ['url' => "https://$local/recursos/"]]]]);             // _elementor_data: barras escapadas \/
$contenido = "<p>Mira mis <a href=\"https://$local/recursos/\">recursos</a></p>";                          // post_content

echo "1. El caso de la clase: $local -> donatesanchez.test (misma longitud, 18 caracteres)\n";
$sql = a_sql($opcion);
$tras = de_sql(reemplazo_ingenuo($sql, $local, 'donatesanchez.test'));
check('«Reemplazar todo» funciona: el serializado sigue siendo válido', unserialize($tras) !== false, substr($tras, 0, 60) . '…');

echo "\n2. Clonar a un dominio de otra longitud: $local -> carlos.sanchezdonate.com (24 caracteres)\n";
$nuevo = 'carlos.sanchezdonate.com';
$tras = de_sql(reemplazo_ingenuo($sql, $local, $nuevo));
$roto = @unserialize($tras);
check('«Reemplazar todo» rompe el serializado (la longitud s:N ya no cuadra)', $roto === false, preg_replace('/.*(s:\d+:"https[^"]+").*/', '$1', $tras));
$seguro = reemplazo_seguro($opcion, $local, $nuevo);
$bien = unserialize($seguro);
check('el reemplazo que deserializa recalcula la longitud y el dato vuelve', is_array($bien) && $bien['url'] === "https://$nuevo/contacto/", preg_replace('/.*(s:\d+:"https[^"]+").*/', '$1', $seguro));

echo "\n3. Qué buscar: la URL entera o solo el dominio\n";
$conUrl = reemplazo_ingenuo($elementor, "https://$local", "https://$nuevo");
check('buscando «https://dominio» NO se cambia el JSON de Elementor (las barras van escapadas: https:\/\/)', $conUrl === $elementor, $elementor);
$conDominio = reemplazo_ingenuo($elementor, $local, $nuevo);
check('buscando solo el dominio, como en clase, SÍ se cambia', str_contains($conDominio, $nuevo) && json_decode($conDominio, true) !== null);
check('y el contenido HTML normal se cambia en los dos casos', str_contains(reemplazo_ingenuo($contenido, "https://$local", "https://$nuevo"), $nuevo));

echo "\n4. Contar el dominio en un volcado (.sql y .sql.gz)\n";
$tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'clonar-bd';
@mkdir($tmp);
$volcado = "CREATE TABLE `wp_options` (`option_value` longtext) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n"
    . 'INSERT INTO `wp_options` VALUES (' . a_sql($opcion) . ");\n"
    . 'INSERT INTO `wp_postmeta` VALUES (' . a_sql($elementor) . ");\n"
    . 'INSERT INTO `wp_posts` VALUES (' . a_sql($contenido) . ");\n";
file_put_contents("$tmp/ejemplo.sql", $volcado);
file_put_contents("$tmp/ejemplo.sql.gz", gzencode($volcado));
$c1 = contar_dominio("$tmp/ejemplo.sql", $local);
$c2 = contar_dominio("$tmp/ejemplo.sql.gz", $local);
echo '     ' . json_encode($c1) . "\n";
check('cuenta 3 apariciones: 2 URL normales, 1 en JSON escapado, 1 dentro de serializado', $c1['total'] === 3 && $c1['plano'] === 2 && $c1['json'] === 1 && $c1['serializadas'] === 1);
check('el .sql.gz da lo mismo que el .sql', $c1 == $c2);

echo "\n5. El wp-config condicional de la clase frente a uno por variable de entorno (php -S, puerto 8138)\n";
$php = PHP_BINARY;
$puerto = 8138;
function servir(string $php, int $puerto, array $env)
{
    $p = proc_open([$php, '-S', "127.0.0.1:$puerto", '-t', __DIR__], [1 => ['file', 'NUL', 'w'], 2 => ['file', 'NUL', 'w']], $pipes, __DIR__, $env);
    for ($i = 0; $i < 40; $i++) {
        usleep(250000);
        if (@fsockopen('127.0.0.1', $puerto)) {
            break;
        }
    }
    return $p;
}
function pedir(int $puerto, string $config, string $host): array
{
    $out = shell_exec('curl.exe -s -H "Host: ' . $host . '" "http://127.0.0.1:' . $puerto . '/ver-config.php?config=' . $config . '"');
    return json_decode((string) $out, true) ?? [];
}
$envSinTipo = getenv();
unset($envSinTipo['WP_ENVIRONMENT_TYPE']);
$srv = servir($php, $puerto, $envSinTipo);
$prod = pedir($puerto, 'carlos', 'carlos.sanchezdonate.com');
$stag = pedir($puerto, 'carlos', 'test-carlos.sanchezdonate.com');
$falso = pedir($puerto, 'carlos', '203.0.113.7');
$seg = pedir($puerto, 'seguro', '203.0.113.7');
proc_terminate($srv);
proc_close($srv);
echo '     Host producción: ' . json_encode($prod) . "\n";
echo '     Host staging:    ' . json_encode($stag) . "\n";
echo '     Host inventado:  ' . json_encode($falso) . "\n";
check('Carlos, Host de producción: BD de producción y sin depuración', $prod['DB_NAME'] === '*******' && $prod['WP_DEBUG_DISPLAY'] === false);
check('Carlos, Host de staging: muestra errores en pantalla (staging suele ser público)', $stag['DB_NAME'] === 'staging-*****' && $stag['WP_DEBUG_DISPLAY'] === true);
check('Carlos, Host inventado: rama local (root sin contraseña) y errores en pantalla', $falso['DB_USER'] === 'root' && $falso['WP_DEBUG_DISPLAY'] === true);
check('seguro, mismo Host inventado: producción y sin depuración (el Host no decide)', $seg['WP_ENVIRONMENT_TYPE'] === 'production' && $seg['WP_DEBUG_DISPLAY'] === false);

$cli = json_decode((string) shell_exec('"' . $php . '" "' . __DIR__ . '/ver-config.php" carlos'), true);
echo '     CLI (wp-cli, cron): ' . json_encode($cli) . "\n";
check('Carlos por línea de comandos: aviso por HTTP_HOST inexistente y BD local', !empty($cli['avisos']) && str_contains($cli['avisos'][0], 'HTTP_HOST') && $cli['DB_USER'] === 'root');

$srv = servir($php, $puerto, $envSinTipo + ['WP_ENVIRONMENT_TYPE' => 'local']);
$loc = pedir($puerto, 'seguro', 'loquesea.test');
proc_terminate($srv);
proc_close($srv);
check('seguro con WP_ENVIRONMENT_TYPE=local en el servidor: BD local y depuración', $loc['WP_ENVIRONMENT_TYPE'] === 'local' && $loc['DB_USER'] === 'root' && $loc['WP_DEBUG_DISPLAY'] === true);

echo "\n$ok/$total comprobaciones OK\n";
exit($ok === $total ? 0 : 1);
