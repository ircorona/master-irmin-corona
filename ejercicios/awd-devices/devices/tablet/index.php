<?php
/**
 * Versión TABLET.
 *
 * Llega aquí quien case con "ipad|tablet|kindle|playbook|silk", o con
 * "android" SIN "mobile" (que es como se distingue una tablet Android de un
 * teléfono Android).
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Hola mundo — versión tablet</title>
<style>
    body { font-family: system-ui, sans-serif; margin: 0; background: #431407; color: #ffedd5; }
    main { max-width: 45rem; margin: 0 auto; padding: 3rem 2rem; }
    .etiqueta { display: inline-block; background: #fb923c; color: #431407; font-weight: 700;
                padding: .4rem 1rem; border-radius: 999px; letter-spacing: .05em; }
    h1 { font-size: 2.75rem; margin: 1.5rem 0 .5rem; }
    p { line-height: 1.6; }
    code { background: #290a02; padding: .15rem .4rem; border-radius: .25rem; color: #fdba74; }
    .datos { background: #290a02; border-radius: .75rem; padding: 1.25rem; margin-top: 2rem; }
    .datos dt { color: #fdba74; font-size: .8rem; text-transform: uppercase; letter-spacing: .05em; }
    .datos dd { margin: .25rem 0 .75rem; word-break: break-all; }
</style>
</head>
<body>
<main>
    <span class="etiqueta">TABLET</span>
    <h1>Hola mundo</h1>
    <p>Estás viendo <code>devices/tablet/index.php</code> en la URL
       <code>/ejercicios/awd-devices/</code>.</p>
    <p><strong>Aviso honesto:</strong> un iPad con Safari en «Solicitar sitio para ordenador»
       —que es el ajuste por defecto desde iPadOS 13— se identifica como
       <code>Macintosh</code> y <strong>no llega aquí</strong>. Por User-Agent es
       indistinguible de un Mac de escritorio. No tiene arreglo en el servidor.</p>

    <dl class="datos">
        <dt>User-Agent</dt>
        <dd><?= htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? '(ninguno)', ENT_QUOTES, 'UTF-8') ?></dd>
        <dt>URL pedida</dt>
        <dd><?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '', ENT_QUOTES, 'UTF-8') ?></dd>
    </dl>
</main>
</body>
</html>
