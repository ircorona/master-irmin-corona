<?php
// Monta dashboard.html sin aislar y aislado, y mira qué componentes llegan al DOM.
// Uso: php prueba.php [ruta-a-chrome]

$chrome = $argv[1] ?? 'C:/Program Files/Google/Chrome/Application/chrome.exe';
$puerto = 8098;
$base   = "http://127.0.0.1:$puerto/dashboard.html";

$servidor = proc_open([PHP_BINARY, '-S', "127.0.0.1:$puerto", '-t', __DIR__],
    [1 => ['file', 'NUL', 'w'], 2 => ['file', 'NUL', 'w']], $tuberias);
usleep(500000);

$dom = fn(string $url) => (string) shell_exec('"' . $chrome . '" --headless --disable-gpu --no-first-run --user-data-dir='
    . escapeshellarg(sys_get_temp_dir() . '/chrome-dash-' . uniqid()) . ' --dump-dom ' . escapeshellarg($url) . ' 2>NUL');

// Sin los <script>: su código fuente también contiene los id="..." como texto
$sinScripts = fn(string $html) => preg_replace('#<script.*?</script>#s', '', $html);
$sinAislar = $sinScripts($dom($base));
$aislado   = $sinScripts($dom("$base?aislado=1"));
proc_terminate($servidor);

$ids = ['estadisticas' => 'Estadísticas', 'posts' => 'Últimos posts', 'comentarios' => 'Comentarios', 'UltimosComentarios-error' => 'Aviso de error'];
printf("%-12s %-10s %s\n", 'Sin aislar', 'Aislado', 'Componente');
$tiene = fn(string $html, string $id) => str_contains($html, "id=\"$id\"") ? 'pintado' : '-';
foreach ($ids as $id => $nombre) {
    printf("%-12s %-10s %s\n", $tiene($sinAislar, $id), $tiene($aislado, $id), $nombre);
}

$ok = $tiene($sinAislar, 'estadisticas') === '-' && $tiene($sinAislar, 'posts') === '-'
   && $tiene($aislado, 'estadisticas') === 'pintado' && $tiene($aislado, 'posts') === 'pintado'
   && $tiene($aislado, 'UltimosComentarios-error') === 'pintado';
echo $ok ? "\nOK: sin aislar se pierde todo; aislado solo cae el componente roto\n" : "\nINESPERADO\n";
exit($ok ? 0 : 1);
