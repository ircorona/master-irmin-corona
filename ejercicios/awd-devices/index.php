<?php
/**
 * Versión de ESCRITORIO — el caso por defecto.
 *
 * A este fichero solo llegan los agentes que no ha desviado el .htaccess.
 * La URL es /ejercicios/awd-devices/ y no cambia para nadie: los móviles ven
 * otro HTML en esta misma URL. Eso es dynamic serving.
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>AWD — versión de escritorio</title>
<style>
    body { font-family: system-ui, sans-serif; margin: 0; background: #0f172a; color: #e2e8f0; }
    main { max-width: 60rem; margin: 0 auto; padding: 4rem 2rem; }
    .etiqueta { display: inline-block; background: #38bdf8; color: #0f172a; font-weight: 700;
                padding: .4rem 1rem; border-radius: 999px; letter-spacing: .05em; }
    h1 { font-size: 3rem; margin: 1.5rem 0 .5rem; }
    .columnas { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 2.5rem; }
    .caja { background: #1e293b; border: 1px solid #334155; border-radius: .75rem; padding: 1.5rem; }
    code { background: #020617; padding: .15rem .4rem; border-radius: .25rem; color: #7dd3fc; }
    dt { color: #94a3b8; font-size: .85rem; text-transform: uppercase; letter-spacing: .05em; }
    dd { margin: .25rem 0 1rem; word-break: break-all; }
</style>
</head>
<body>
<main>
    <span class="etiqueta">ESCRITORIO</span>
    <h1>Versión de ordenador</h1>
    <p>Este HTML lo sirve <code>ejercicios/awd-devices/index.php</code>, que es el caso por defecto:
       ningún agente ha casado con las reglas de tablet ni de móvil del <code>.htaccess</code>.</p>

    <div class="columnas">
        <div class="caja">
            <h2>Lo que hace que esto sea AWD</h2>
            <p>La URL <strong>no ha cambiado</strong>. Un móvil pidiendo esta misma dirección
               recibe otro HTML — el de <code>devices/movil/</code> — sin redirección y sin
               ver ese path en la barra del navegador.</p>
            <p>Por eso <strong>no hace falta</strong> el par <code>canonical</code>/<code>alternate</code>:
               solo hay una URL que indexar.</p>
        </div>
        <div class="caja">
            <h2>Lo que sí hace falta</h2>
            <p>La cabecera <code>Vary: User-Agent</code>, que pone el <code>.htaccess</code>
               con <code>mod_headers</code>. Sin ella, una caché intermedia puede servirle
               a un ordenador la versión que guardó para un móvil.</p>
        </div>
    </div>

    <div class="caja" style="margin-top:2rem">
        <h2>Lo que ha visto el servidor</h2>
        <dl>
            <dt>User-Agent</dt>
            <dd><?= htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? '(ninguno)', ENT_QUOTES, 'UTF-8') ?></dd>
            <dt>URL pedida</dt>
            <dd><?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '', ENT_QUOTES, 'UTF-8') ?></dd>
            <dt>Fichero que responde</dt>
            <dd>index.php (escritorio)</dd>
        </dl>
    </div>
</main>
</body>
</html>
