<?php
// Pruebas de validar.php. Uso: php test.php  (sale con 1 si alguna falla)
require __DIR__ . '/validar.php';

$base = ['nombre' => 'Carlos Manuel Sánchez Donate', 'email' => 'carlos@sanchezdonate.com', 'pass' => 'contraseña1', 'pass2' => 'contraseña1'];

$casos = [
    'todo correcto'          => [[], []],
    'coma en vez de punto'   => [['email' => 'carlos@sanchezdonate,com'], ['email' => 'Has escrito una coma en lugar de un punto. ¿Querías decir carlos@sanchezdonate.com?']],
    'sin arroba'             => [['email' => 'carlos.sanchezdonate.com'], ['email' => 'Falta la @. El correo tiene la forma nombre@dominio.com.']],
    'dos arrobas'            => [['email' => 'carlos@@sanchezdonate.com'], ['email' => 'Hay más de una @. El correo solo lleva una: nombre@dominio.com.']],
    'espacio dentro'         => [['email' => 'carlos @sanchezdonate.com'], ['email' => 'El correo no puede llevar espacios. ¿Querías decir carlos@sanchezdonate.com?']],
    'dominio sin extensión'  => [['email' => 'carlos@sanchezdonate'], ['email' => 'Al dominio «sanchezdonate» le falta la terminación, por ejemplo sanchezdonate.com.']],
    'espacios alrededor ok'  => [['email' => '  carlos@sanchezdonate.com  '], []],
    'contraseña corta'       => [['pass' => 'abc', 'pass2' => 'abc'], ['pass' => 'La contraseña debe tener al menos 8 caracteres (ahora tiene 3).']],
    'contraseñas distintas'  => [['pass2' => 'otra-cosa1'], ['pass2' => 'Las contraseñas no coinciden. Vuelve a escribir la confirmación.']],
    'nombre vacío'           => [['nombre' => '   '], ['nombre' => 'Escribe tu nombre.']],
    'caso de la diapositiva' => [['email' => 'carlos@sanchezdonate,com', 'pass2' => 'distinta12'], [
        'email' => 'Has escrito una coma en lugar de un punto. ¿Querías decir carlos@sanchezdonate.com?',
        'pass2' => 'Las contraseñas no coinciden. Vuelve a escribir la confirmación.',
    ]],
];

$fallos = 0;
foreach ($casos as $nombre => [$cambios, $esperado]) {
    $obtenido = validar_registro(array_merge($base, $cambios));
    $ok = $obtenido === $esperado;
    $fallos += $ok ? 0 : 1;
    echo ($ok ? 'OK   ' : 'FALLO') . "  $nombre\n";
    if (!$ok) {
        echo '       esperado: ' . json_encode($esperado, JSON_UNESCAPED_UNICODE) . "\n";
        echo '       obtenido: ' . json_encode($obtenido, JSON_UNESCAPED_UNICODE) . "\n";
    }
}
$total = count($casos);
echo "\n" . ($total - $fallos) . "/$total casos correctos\n";
exit($fallos ? 1 : 0);
