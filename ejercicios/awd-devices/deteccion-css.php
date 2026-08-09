<?php
/**
 * Variante MIXTA del AWD: un solo HTML, y lo único que cambia según el
 * dispositivo es la hoja de estilos.
 *
 * Es la versión corregida del tercer bloque del código de auxilio de la clase.
 * Tres cambios respecto al original:
 *
 *   1. La cabecera Vary: User-Agent. El original no la envía, y sin ella
 *      cualquier caché intermedia puede servir el HTML equivocado.
 *   2. Se comprueba la TABLET ANTES que el móvil. En el original, $isMobile
 *      incluía "ipad" y se evaluaba primero en el if/elseif, así que un iPad
 *      nunca llegaba a la rama de tablet: el CSS de tablet era código muerto.
 *   3. Las tablets Android se separan de los teléfonos Android por el token
 *      "Mobile". En el original, "android" estaba en las dos expresiones
 *      regulares y ganaba siempre la primera.
 */

// Aviso a cachés y a Google: el contenido de esta URL depende del agente.
// Tiene que salir ANTES de cualquier salida de texto.
header('Vary: User-Agent');

$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

$esAndroid   = stripos($ua, 'android') !== false;
$tieneMobile = stripos($ua, 'mobile')  !== false;

// Tablet: las declaradas, más Android sin el token "Mobile".
$esTablet = preg_match('/ipad|tablet|kindle|playbook|silk/i', $ua)
            || ($esAndroid && !$tieneMobile);

// Móvil: solo si NO es tablet. El orden es lo que hace que esto funcione.
$esMovil = !$esTablet
           && preg_match('/android|iphone|ipod|blackberry|iemobile|opera mini|opera mobi|palmos|webos|windows phone/i', $ua);

if ($esTablet) {
    $hoja      = 'assets/css/tablet.css';
    $version   = 'TABLET';
} elseif ($esMovil) {
    $hoja      = 'assets/css/movil.css';
    $version   = 'MÓVIL';
} else {
    $hoja      = 'assets/css/escritorio.css';
    $version   = 'ESCRITORIO';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>AWD mixto — un HTML, tres hojas de estilo</title>
<link rel="stylesheet" href="<?= htmlspecialchars($hoja, ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
<main>
    <span class="etiqueta"><?= $version ?></span>
    <h1>Hola mundo</h1>
    <p>El HTML de esta página es <strong>exactamente el mismo</strong> para los tres
       dispositivos. Lo único que ha decidido el servidor es qué fichero CSS enlazar:
       <code><?= htmlspecialchars($hoja, ENT_QUOTES, 'UTF-8') ?></code>.</p>

    <p class="nota">Este enfoque es el menos arriesgado de los tres para SEO:
       el HTML no varía, así que Googlebot ve siempre el mismo contenido y
       la superficie de cloaking accidental es cero. Solo cambia la presentación.</p>

    <dl>
        <dt>User-Agent</dt>
        <dd><?= htmlspecialchars($ua !== '' ? $ua : '(ninguno)', ENT_QUOTES, 'UTF-8') ?></dd>
        <dt>Hoja servida</dt>
        <dd><?= htmlspecialchars($hoja, ENT_QUOTES, 'UTF-8') ?></dd>
    </dl>
</main>
</body>
</html>
