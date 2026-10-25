<?php
// SSR vs SSG vs ISR con un reloj simulado (segundos), sin esperar de verdad.
// El contenido de la "base de datos" cambia en t=10. Cada estrategia responde
// a las mismas peticiones; ISR usa revalidate = 60, como `export const revalidate = 60` en Next.js.

$db = fn(int $t) => $t < 10 ? 'precio 100 EUR' : 'precio 80 EUR';
$peticiones = [0, 30, 61, 62, 200];
$revalidate = 60;

// SSR: genera en cada petición. Siempre fresco, siempre cuesta servidor.
echo "SSR (render en cada petición)\n";
foreach ($peticiones as $t) {
    printf("  t=%3d  %-13s render=sí\n", $t, $db($t));
}

// SSG: se genera una vez en el build (t=0). Hasta el siguiente deploy, nada cambia.
echo "\nSSG (build en t=0, sin redeploy)\n";
$build = $db(0);
foreach ($peticiones as $t) {
    printf("  t=%3d  %-13s render=no\n", $t, $build);
}

// ISR: sirve la copia estática; si caducó, la sirve igualmente (STALE)
// y la regenera en segundo plano para la siguiente visita.
echo "\nISR (revalidate=$revalidate)\n";
$cache = ['html' => $db(0), 'generado' => 0];
$regenerando = false;
foreach ($peticiones as $t) {
    if ($regenerando) {                       // terminó la regeneración de la petición anterior
        $cache = ['html' => $db($t), 'generado' => $t];
        $regenerando = false;
    }
    $estado = ($t - $cache['generado'] >= $revalidate) ? 'STALE' : 'HIT';
    printf("  t=%3d  %-13s x-nextjs-cache: %s\n", $t, $cache['html'], $estado);
    if ($estado === 'STALE') {
        $regenerando = true;
    }
}
