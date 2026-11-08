<?php
/**
 * Buscar y reemplazar el dominio en una base de datos de WordPress: a mano (como "Reemplazar todo"
 * en Notepad++) frente a respetando los datos serializados (como hace wp search-replace).
 *
 * Funciones reutilizables; prueba.php las ejecuta con datos de ejemplo.
 */

/** Lo que hace "Reemplazar todo" sin coincidir mayúsculas: texto plano, sin entender lo que hay dentro. */
function reemplazo_ingenuo(string $texto, string $viejo, string $nuevo): string
{
    return str_ireplace($viejo, $nuevo, $texto);
}

/** Reemplazo que deserializa, cambia y vuelve a serializar (recalcula las longitudes s:N). */
function reemplazo_seguro(string $valor, string $viejo, string $nuevo): string
{
    $datos = @unserialize($valor, ['allowed_classes' => false]);
    if ($datos === false && $valor !== serialize(false)) {
        return str_ireplace($viejo, $nuevo, $valor);   // no es serializado: texto plano
    }
    return serialize(reemplazar_recursivo($datos, $viejo, $nuevo));
}

function reemplazar_recursivo($dato, string $viejo, string $nuevo)
{
    if (is_string($dato)) {
        // Puede haber serializado dentro de serializado
        $interno = @unserialize($dato, ['allowed_classes' => false]);
        if ($interno !== false || $dato === serialize(false)) {
            return serialize(reemplazar_recursivo($interno, $viejo, $nuevo));
        }
        return str_ireplace($viejo, $nuevo, $dato);
    }
    if (is_array($dato)) {
        foreach ($dato as $k => $v) {
            $dato[$k] = reemplazar_recursivo($v, $viejo, $nuevo);
        }
    }
    return $dato;
}

/** Cómo queda un valor dentro de un INSERT de un volcado SQL (comillas y barras escapadas). */
function a_sql(string $valor): string
{
    return "'" . addslashes($valor) . "'";
}

function de_sql(string $literal): string
{
    return stripslashes(substr($literal, 1, -1));
}

/**
 * Cuenta en un volcado .sql o .sql.gz cuántas veces aparece el dominio y en qué forma.
 * No imprime contenido: solo cifras.
 */
function contar_dominio(string $fichero, string $dominio): array
{
    $texto = str_ends_with($fichero, '.gz')
        ? implode('', gzfile($fichero))
        : file_get_contents($fichero);
    $d = preg_quote($dominio, '/');
    $total = preg_match_all("/$d/i", $texto);
    // URL con barras normales: https://dominio
    $plano = preg_match_all("/https?:\/\/(www\.)?$d/i", $texto);
    // URL en JSON escapado dentro de SQL: https:\\/\\/dominio (Elementor, bloques, opciones JSON)
    $json = preg_match_all('/https?:\\\\{1,2}\/\\\\{1,2}\/(www\.)?' . $d . '/i', $texto);
    // Cadenas serializadas que contienen el dominio: s:N:"...dominio..." (mysqldump escapa las
    // comillas como \"; UpdraftPlus no). La barra es opcional para contar los dos formatos.
    // Sin una regex sobre todo el volcado (revienta el límite de PCRE en ficheros grandes): se lee
    // la longitud N de cada cabecera s:N:" y se mira el dominio solo dentro de esos N bytes.
    $serializadas = 0;
    preg_match_all('/s:(\d+):(\\\\?)"/', $texto, $cab, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
    foreach ($cab as $c) {
        $n = (int) $c[1][0];
        $inicio = $c[0][1] + strlen($c[0][0]);
        // si el volcado escapa con \ el largo real crece; margen de un 20 %
        $ventana = substr($texto, $inicio, $c[2][0] === '' ? $n : (int) ($n * 1.2) + 2);
        if (stripos($ventana, $dominio) !== false) {
            $serializadas++;
        }
    }
    $charsets = [];
    preg_match_all('/DEFAULT CHARSET=(\w+)/i', $texto, $m);
    foreach ($m[1] as $c) {
        $charsets[$c] = ($charsets[$c] ?? 0) + 1;
    }
    return compact('total', 'plano', 'json', 'serializadas', 'charsets');
}
