<?php
// Validación del formulario «Crear cuenta». Devuelve [campo => mensaje].
// Cada mensaje dice qué falla y cómo arreglarlo (heurística 9).

function validar_registro(array $datos): array
{
    $errores = [];

    $nombre = trim($datos['nombre'] ?? '');
    $email  = trim($datos['email'] ?? '');
    $pass   = (string) ($datos['pass'] ?? '');
    $pass2  = (string) ($datos['pass2'] ?? '');

    if ($nombre === '') {
        $errores['nombre'] = 'Escribe tu nombre.';
    }

    if ($email === '') {
        $errores['email'] = 'Escribe tu correo electrónico.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores['email'] = mensaje_email($email);
    }

    // Caracteres, no bytes («ñ» cuenta 1). Con PCRE para no depender de mbstring;
    // si llega UTF-8 inválido, preg devuelve false y se cuentan bytes.
    $largo = preg_match_all('/./su', $pass);
    if ($largo === false) {
        $largo = strlen($pass);
    }
    if ($largo < 8) {
        $errores['pass'] = "La contraseña debe tener al menos 8 caracteres (ahora tiene $largo).";
    }

    if ($pass2 === '') {
        $errores['pass2'] = 'Repite la contraseña para confirmarla.';
    } elseif ($pass !== $pass2) {
        $errores['pass2'] = 'Las contraseñas no coinciden. Vuelve a escribir la confirmación.';
    }

    return $errores;
}

// Diagnostica el error más probable y, si puede, propone el correo corregido.
function mensaje_email(string $email): string
{
    if (substr_count($email, '@') === 0) {
        return 'Falta la @. El correo tiene la forma nombre@dominio.com.';
    }
    if (substr_count($email, '@') > 1) {
        return 'Hay más de una @. El correo solo lleva una: nombre@dominio.com.';
    }
    if (str_contains($email, ' ')) {
        $sugerido = str_replace(' ', '', $email);
        if (filter_var($sugerido, FILTER_VALIDATE_EMAIL)) {
            return "El correo no puede llevar espacios. ¿Querías decir $sugerido?";
        }
    }
    if (str_contains($email, ',')) {
        $sugerido = str_replace(',', '.', $email);
        if (filter_var($sugerido, FILTER_VALIDATE_EMAIL)) {
            return "Has escrito una coma en lugar de un punto. ¿Querías decir $sugerido?";
        }
    }
    [, $dominio] = explode('@', $email, 2);
    if (!str_contains($dominio, '.')) {
        return "Al dominio «{$dominio}» le falta la terminación, por ejemplo {$dominio}.com.";
    }
    return 'El correo no es válido. Revisa que tenga la forma nombre@dominio.com.';
}
