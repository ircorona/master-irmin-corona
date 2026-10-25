<?php
// Librería: TÚ llamas a su código cuando quieres. El flujo del programa es tuyo.
// Mismo papel que FPDF: una clase que hace una cosa (PDF) y no decide nada más.

final class Slug
{
    public static function crear(string $texto): string
    {
        // iconv('ASCII//TRANSLIT') en Windows deja "qu'e": mapa explícito.
        $texto = strtr($texto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ü' => 'u', 'ñ' => 'n', 'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ñ' => 'N']);
        $texto = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $texto));
        return trim($texto, '-');
    }
}

// --- Mi código: yo decido el orden, los nombres, dónde va cada cosa ---
$titulos = ['¿Qué es un framework?', 'Librería vs framework', 'ISR: la revolución'];

foreach ($titulos as $t) {
    echo str_pad('/blog/' . Slug::crear($t), 30) . "<- $t\n";
}
