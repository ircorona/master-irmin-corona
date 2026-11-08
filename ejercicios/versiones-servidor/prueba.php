<?php
/**
 * Prueba del ejercicio con dos PHP de Laragon (8.3 y 8.5).
 * Uso: php prueba.php
 */

$aqui = __DIR__;
$tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'versiones-servidor';
@mkdir($tmp);
$php83 = 'C:/laragon/bin/php/php-8.3.26-Win32-vs16-x64/php.exe';
$php85 = 'C:/laragon/bin/php/php-8.5.7-nts-Win32-vs17-x64/php.exe';
$puerto = 8137;
$ok = 0;
$total = 0;

function correr(string $cmd): array
{
    $out = [];
    exec($cmd . ' 2>&1', $out, $code);
    return [$code, implode("\n", $out)];
}

function check(string $nombre, bool $cond, string $detalle = ''): void
{
    global $ok, $total;
    $total++;
    $ok += $cond ? 1 : 0;
    echo ($cond ? '  [OK] ' : '  [FALLO] ') . $nombre . ($detalle !== '' ? "  -> $detalle" : '') . "\n";
}

foreach ([$php83, $php85] as $bin) {
    if (!is_file($bin)) {
        echo "No encuentro $bin\n";
        exit(2);
    }
}

echo "1. Fotos del entorno por CLI (8.3 = 'servidor viejo', 8.5 = 'servidor nuevo')\n";
[$c1] = correr("\"$php83\" \"$aqui/foto-entorno.php\" > \"$tmp/viejo.json\"");
[$c2] = correr("\"$php85\" \"$aqui/foto-entorno.php\" > \"$tmp/nuevo.json\"");
$v = json_decode(file_get_contents("$tmp/viejo.json"), true);
$n = json_decode(file_get_contents("$tmp/nuevo.json"), true);
check('las dos fotos son JSON válido', is_array($v) && is_array($n), ($v['php'] ?? '?') . ' / ' . ($n['php'] ?? '?'));
[$cc, $salida] = correr("\"$php85\" \"$aqui/comparar.php\" \"$tmp/viejo.json\" \"$tmp/nuevo.json\"");
echo preg_replace('/^/m', '     ', $salida), "\n";
check('comparar.php detecta las diferencias', str_contains($salida, 'PHP: 8.3') && str_contains($salida, '8.5'), "exit $cc");

echo "\n2. phpinfo() seguro por web (php -S, puerto $puerto)\n";
$srv = proc_open([$php85, '-S', "127.0.0.1:$puerto", '-t', $aqui], [1 => ['file', 'NUL', 'w'], 2 => ['file', 'NUL', 'w']], $pipes);
$html = false;
for ($i = 0; $i < 40 && $html === false; $i++) {
    usleep(250000);
    $html = @file_get_contents("http://127.0.0.1:$puerto/foto-entorno.php?html=1");
}
$json = @file_get_contents("http://127.0.0.1:$puerto/foto-entorno.php");
proc_terminate($srv);
proc_close($srv);
$web = json_decode((string) $json, true);
check('por web la SAPI es otra (cli-server), la foto la marca', ($web['sapi'] ?? '') === 'cli-server', $web['sapi'] ?? 'sin respuesta');
check('el phpinfo() web trae la configuración y los módulos', $html !== false && str_contains($html, 'Loaded Configuration File') && str_contains($html, 'memory_limit'));
check('y NO trae variables de entorno ni de petición', $html !== false && !str_contains($html, 'PHP Variables') && !str_contains($html, '$_SERVER[') && !str_contains($html, '>Environment<'));

echo "\n3. La plantilla antigua en PHP 8.3 y 8.5\n";
[$code, $err] = correr("\"$php83\" \"$aqui/viejo/plantilla-antigua.php7\"");
echo '     8.3: ' . trim(strtok($err, "\n")) . "\n";
check('8.3: no llega a ejecutarse, y el mensaje nombra la causa (llaves)', $code !== 0 && stripos($err, 'curly braces') !== false, "exit $code");
[$code, $err] = correr("\"$php85\" \"$aqui/viejo/plantilla-antigua.php7\"");
echo '     8.5: ' . trim(strtok($err, "\n")) . "\n";
check('8.5: tampoco se ejecuta, pero el mensaje es un error de sintaxis genérico', $code !== 0 && stripos($err, 'syntax error') !== false && stripos($err, 'curly') === false, "exit $code");

echo "\n4. Escáner\n";
[$code, $sal] = correr("\"$php85\" \"$aqui/escanear.php\" \"$aqui/viejo/plantilla-antigua.php7\"");
echo preg_replace('/^/m', '     ', $sal), "\n";
check('encuentra each(), create_function() y las llaves', $code === 1 && str_contains($sal, 'each()') && str_contains($sal, 'create_function()') && str_contains($sal, '{...}'));
[$code, $sal] = correr("\"$php85\" \"$aqui/escanear.php\" \"$aqui/escanear.php\"");
check('no da falsos positivos con los nombres escritos como texto (su propio código)', $code === 0, trim($sal));

echo "\n5. El cambio silencioso\n";
[$code, $sal] = correr("\"$php85\" \"$aqui/viejo/comparacion-silenciosa.php\"");
echo '     ' . trim($sal) . "\n";
check('en PHP 8, 0 == "todas" es false y la plantilla cambia de rama sin dar error', $code === 0 && str_contains($sal, '-> false') && str_contains($sal, 'página todas'));

echo "\n$ok/$total comprobaciones OK\n";
exit($ok === $total ? 0 : 1);
