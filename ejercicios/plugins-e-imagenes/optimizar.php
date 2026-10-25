<?php
// Optimiza una imagen en el servidor con GD: la redimensiona a varios anchos y la
// guarda en JPEG, WebP y AVIF. Imprime el peso de cada versión y el <picture> listo.
// Uso (PHP 8.3 de Laragon, el que trae GD con WebP y AVIF):
//   php optimizar.php [imagen] [carpeta-salida]

$origen = $argv[1] ?? __DIR__ . '/../../images/how_do_you_turn_this_on.jpg';
$salida = $argv[2] ?? sys_get_temp_dir() . '/optimizar-' . getmypid();

if (!function_exists('imageavif') || !function_exists('imagewebp')) {
    fwrite(STDERR, "Este PHP no tiene GD con WebP y AVIF. Usa el 8.3 de Laragon.\n");
    exit(1);
}
@mkdir($salida, 0777, true);

$imagen = imagecreatefromstring(file_get_contents($origen));
$anchoOriginal = imagesx($imagen);
$pesoOriginal = filesize($origen);
$nombre = pathinfo($origen, PATHINFO_FILENAME);

printf("Original: %s, %dx%d, %s KB\n\n", basename($origen), $anchoOriginal, imagesy($imagen), round($pesoOriginal / 1024, 1));
printf("%-6s %10s %10s %10s\n", 'Ancho', 'JPEG q82', 'WebP q80', 'AVIF q50');

$anchos = array_values(array_filter([400, 800, $anchoOriginal], fn($a) => $a <= $anchoOriginal));
$anchos = array_unique($anchos);
$pesos = [];
foreach ($anchos as $ancho) {
    $copia = $ancho === $anchoOriginal ? $imagen : imagescale($imagen, $ancho);
    $base = "$salida/$nombre-$ancho";
    imagejpeg($copia, "$base.jpg", 82);
    imagewebp($copia, "$base.webp", 80);
    imageavif($copia, "$base.avif", 50, 6);
    clearstatcache();
    $pesos[$ancho] = [filesize("$base.jpg"), filesize("$base.webp"), filesize("$base.avif")];
    printf("%-6d %8.1f KB %8.1f KB %8.1f KB\n", $ancho, ...array_map(fn($b) => $b / 1024, $pesos[$ancho]));
}

[$jpg, $webp, $avif] = $pesos[$anchoOriginal];
printf("\nMismo ancho que el original: WebP %d %% y AVIF %d %% menos que el JPEG original\n",
    round(100 - $webp * 100 / $pesoOriginal), round(100 - $avif * 100 / $pesoOriginal));

$srcset = fn($ext) => implode(', ', array_map(fn($a) => "$nombre-$a.$ext {$a}w", $anchos));
echo "\n<picture>\n";
echo "  <source type=\"image/avif\" srcset=\"{$srcset('avif')}\" sizes=\"(max-width: 600px) 100vw, 800px\">\n";
echo "  <source type=\"image/webp\" srcset=\"{$srcset('webp')}\" sizes=\"(max-width: 600px) 100vw, 800px\">\n";
echo "  <img src=\"$nombre-$anchoOriginal.jpg\" srcset=\"{$srcset('jpg')}\" sizes=\"(max-width: 600px) 100vw, 800px\"\n";
echo "       width=\"$anchoOriginal\" height=\"" . imagesy($imagen) . "\" alt=\"...\" loading=\"lazy\">\n";
echo "</picture>\n\nFicheros en: $salida\n";

// Sale con 1 si alguna versión moderna pesa más que el JPEG original
exit(($webp < $pesoOriginal && $avif < $pesoOriginal) ? 0 : 1);
