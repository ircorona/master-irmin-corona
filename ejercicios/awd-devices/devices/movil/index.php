<?php
/**
 * Versión MÓVIL.
 *
 * El .htaccess de la carpeta padre reescribe internamente hasta aquí cuando el
 * User-Agent es un teléfono. La URL que ve el usuario sigue siendo
 * /ejercicios/awd-devices/ — nunca aparece /devices/movil/ en la barra.
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Hola mundo — versión móvil</title>
<style>
    body { font-family: system-ui, sans-serif; margin: 0; background: #052e16; color: #dcfce7;
           display: flex; min-height: 100vh; align-items: center; justify-content: center; }
    main { padding: 1.5rem; text-align: center; width: 100%; }
    .etiqueta { display: inline-block; background: #4ade80; color: #052e16; font-weight: 700;
                padding: .4rem 1rem; border-radius: 999px; letter-spacing: .05em; font-size: .8rem; }
    h1 { font-size: 2.5rem; margin: 1.5rem 0; line-height: 1.1; }
    p { line-height: 1.6; }
    code { background: #022c22; padding: .15rem .4rem; border-radius: .25rem; color: #86efac;
           word-break: break-all; }
    .datos { text-align: left; background: #022c22; border-radius: .75rem; padding: 1rem;
             margin-top: 2rem; font-size: .8rem; }
    .datos dt { color: #86efac; text-transform: uppercase; letter-spacing: .05em; }
    .datos dd { margin: .25rem 0 .75rem; word-break: break-all; }
</style>
</head>
<body>
<main>
    <span class="etiqueta">MÓVIL</span>
    <h1>Hola mundo</h1>
    <p>Estás viendo <code>devices/movil/index.php</code>, pero la URL de la barra
       sigue siendo <code>/ejercicios/awd-devices/</code>.</p>
    <p>Eso es <strong>dynamic serving</strong>: una URL, dos HTML.</p>

    <dl class="datos">
        <dt>User-Agent</dt>
        <dd><?= htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? '(ninguno)', ENT_QUOTES, 'UTF-8') ?></dd>
        <dt>URL pedida</dt>
        <dd><?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '', ENT_QUOTES, 'UTF-8') ?></dd>
    </dl>
</main>
</body>
</html>
