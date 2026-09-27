<?php
/**
 * Plantilla compartida por las dos versiones de idioma.
 *
 * No se sirve directamente: la incluyen `index.php` (es) y `en/index.php` (en),
 * que son los que fijan `$lang` antes de llamarla.
 *
 * Ejercicio: SEO internacional — "Una página, distintos idiomas".
 */

// Si alguien entra directo a este fichero, no hay idioma que servir.
if (!isset($lang)) {
    http_response_code(404);
    exit('Esta plantilla no se sirve directamente.');
}

// -----------------------------------------------------------------------------
// Configuración: el dominio desde el que se sirve el ejercicio.
// Cambia esta línea si lo montas en otro sitio (Laragon, un hosting, etc.).
// -----------------------------------------------------------------------------
$base = 'http://master-irmin-corona.test/ejercicios/idiomas-por-url';

// Una URL por idioma. Estructura de subfolders, que es la que recomienda el módulo.
$urls = [
    'es' => $base . '/',
    'en' => $base . '/en/',
];

// La versión por defecto: la que sirve el `x-default` cuando no encaja ninguna.
$idioma_por_defecto = 'es';

// -----------------------------------------------------------------------------
// Textos. Un bloque por idioma, en lugar del if/else de la clase:
// añadir un tercer idioma es añadir una clave, no otro `elseif`.
// -----------------------------------------------------------------------------
$textos = [
    'es' => [
        'title'    => 'Ejemplos de hreflang',
        'h1'       => 'Ejemplos de hreflang',
        'texto1'   => 'El SEO, o Search Engine Optimization, es una estrategia esencial '
                    . 'para que una web aparezca en los resultados de búsqueda. Esta página '
                    . 'existe en dos idiomas, y cada uno vive en su propia URL.',
        'switch'   => 'Read this page in English',
        'nombre'   => 'Español',
    ],
    'en' => [
        'title'    => 'hreflang examples',
        'h1'       => 'hreflang examples',
        'texto1'   => 'SEO, or Search Engine Optimization, is an essential strategy for '
                    . 'getting a website to show up in search results. This page exists in '
                    . 'two languages, and each one lives at its own URL.',
        'switch'   => 'Leer esta página en español',
        'nombre'   => 'English',
    ],
];

// Si llega un idioma que no existe, se cae al de por defecto en vez de romper.
if (!isset($textos[$lang])) {
    $lang = $idioma_por_defecto;
}

$t = $textos[$lang];

// El "otro" idioma, para el enlace del selector.
$otro = ($lang === 'es') ? 'en' : 'es';

/** Escapa para imprimir dentro de HTML. */
function e($cadena)
{
    return htmlspecialchars($cadena, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="<?php echo e($lang); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo e($t['title']); ?></title>

    <!-- El canonical apunta SIEMPRE a la versión que es, nunca a la hermana. -->
    <link rel="canonical" href="<?php echo e($urls[$lang]); ?>">

    <!--
      hreflang: recíproco y autorreferencial.
      Cada versión se declara a sí misma y declara a la otra, así que las dos
      páginas emiten exactamente el mismo bloque. Si falta el retorno, Google
      descarta el grupo entero.
    -->
<?php foreach ($urls as $codigo => $url): ?>
    <link rel="alternate" hreflang="<?php echo e($codigo); ?>" href="<?php echo e($url); ?>">
<?php endforeach; ?>
    <link rel="alternate" hreflang="x-default" href="<?php echo e($urls[$idioma_por_defecto]); ?>">

    <style>
        body { font-family: system-ui, sans-serif; line-height: 1.6; margin: 0; padding: 2rem; }
        .first-pf { max-width: 600px; margin: 0 auto; }
        nav { max-width: 600px; margin: 0 auto 2rem; }
    </style>
</head>
<body>

    <header>
        <nav>
            <!--
              Tres atributos y cada uno dice una cosa distinta:
                href     -> a donde va
                hreflang -> en que idioma esta la pagina de destino
                lang     -> en que idioma esta ESTE texto del enlace

              El `lang` hace falta porque el texto del selector esta escrito en
              el idioma de destino, no en el de la pagina (W3C, "Declarar idioma
              en HTML"). Y ojo: el `hreflang` de un <a> NO es lo que Google lee
              como version alternativa; eso son los <link> del <head>.
            -->
            <a href="<?php echo e($urls[$otro]); ?>"
               hreflang="<?php echo e($otro); ?>"
               lang="<?php echo e($otro); ?>">
                <?php echo e($t['switch']); ?>
            </a>
        </nav>
    </header>

    <section class="first-pf">
        <h1><?php echo e($t['h1']); ?></h1>
        <p><?php echo e($t['texto1']); ?></p>

        <hr>
        <p><small>
            <code>$lang</code> = <strong><?php echo e($lang); ?></strong>
            (<?php echo e($t['nombre']); ?>) &middot;
            <code><?php echo e($urls[$lang]); ?></code>
        </small></p>
    </section>

</body>
</html>
