<?php
/**
 * Versión corregida del ejercicio de orden y estructura del código.
 * Original: https://codepen.io/barvock/pen/gOQPLPo
 * Fallos detectados y explicados en auditoria.md
 *
 * El bloque PHP va ANTES del <!DOCTYPE>. En el original estaba entre
 * <html> y <head>, así que cualquier salida suya caía fuera del <head>.
 *
 * $url ya NO lleva barra final: en el original la llevaba y luego se
 * escribía <?php echo $url; ?>/servicios-seo/ -> doble barra -> dos URLs
 * para la misma página. Aquí solo se usa donde hace falta una URL
 * absoluta (canonical, og:url, hreflang); los enlaces internos van
 * relativos, que es más corto y no depende del dominio.
 *
 * En un tema real esto sería home_url() y get_template_directory_uri().
 */
$url  = 'https://carlos.sanchezdonate.com';
$tema = '/wp-content/themes/sanchezdonate';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <!-- 1. Codificación y viewport: siempre lo primero -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- 2. Lo que define la página. En el original el <title> aparecía
            60 líneas más abajo, detrás de 3 kB de CSS de un plugin -->
    <title>PRUEBA WPO | El SEO Técnico</title>
    <meta name="description" content="Prueba para una clase de WPO">
    <link rel="canonical" href="<?php echo $url; ?>/">
    <meta name="robots" content="max-image-preview:large">

    <!-- 3. Conexiones y recursos críticos.
            Cuatro peticiones de fuentes (un <link> + tres @import, dos de
            ellos idénticos) reducidas a una sola, con preconnect y solo
            los pesos que el CSS usa de verdad: 200, 400, 600 y 900.
            Lo ideal sería autoalojar los .woff2 (ver clase 07). -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Catamaran:wght@900&family=Source+Sans+3:wght@200;400;600&display=swap">

    <!-- 4. Un solo CSS, externo, minificado y aquí arriba.
            En el original estaba en un <style> al final del <body>. -->
    <link rel="stylesheet" href="<?php echo $tema; ?>/assets/css/principal.css">

    <!-- 5. Lo que no hace falta para pintar: hovers, transiciones,
            footer y el widget del formulario. Carga sin bloquear. -->
    <link rel="stylesheet" href="<?php echo $tema; ?>/assets/css/diferido.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="<?php echo $tema; ?>/assets/css/diferido.css"></noscript>

    <!-- 6. Todo el JS en un fichero con defer: se descarga en paralelo
            y se ejecuta con el DOM ya completo -->
    <script src="<?php echo $tema; ?>/assets/js/app.js" defer></script>

    <!-- 7. Social y favicons: no bloquean nada, van al final -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="PRUEBA WPO | El SEO Técnico">
    <meta property="og:description" content="Prueba para una clase de WPO">
    <meta property="og:url" content="<?php echo $url; ?>/">
    <meta property="og:image" content="<?php echo $url; ?>/wp-content/uploads/carlos-sanchez.jpeg">
    <meta property="og:image:alt" content="PRUEBA WPO el mejor SEO Técnico">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@SEO_Tecnico">
    <meta name="twitter:creator" content="@SEO_Tecnico">

    <link rel="icon" type="image/png" sizes="300x300" href="<?php echo $tema; ?>/images/favicon/favicon.png">
    <link rel="apple-touch-icon" sizes="300x300" href="<?php echo $tema; ?>/images/favicon/favicon.png">

    <link rel="alternate" hreflang="es" href="<?php echo $url; ?>/">
    <link rel="alternate" hreflang="x-default" href="<?php echo $url; ?>/">
    <link rel="alternate" type="application/rss+xml" href="/feed/">
    <link rel="author" type="text/plain" href="<?php echo $tema; ?>/complements/humans.txt">

    <?php
    /* FUERA del <head>, respecto al original:
       - <meta http-equiv="X-UA-Compatible">      IE murió en 2022
       - el CSS del plugin de Brevo               ahora en diferido.css
       - los tres <style> con @import de fuentes  ahora un solo <link>
       - las dos <script> de config de Brevo      van con wp_localize_script,
                                                  y apuntaban a sanchezdonate.test
       - el polyfill de WebP Express              navegadores que ya no existen
       - los <link> de la REST API y oEmbed       se quitan en functions.php
       - <meta name="author">                     no lo usa ningún buscador */
    ?>
</head>

<body>

    <!-- La clase del <header> tenía ~450 caracteres de texto suelto y una
         cadena falsa de clases de Elementor, ninguna con CSS detrás -->
    <header class="site-header">

        <div class="header-anuncio black-bg">
            <div class="header-countdown">
                <!-- El <script> del contador estaba AQUÍ ARRIBA, antes de
                     que existiera este elemento. Ahora va en app.js con
                     defer, y <time> lleva su datetime -->
                <div id="countdowncarlos">Comenzamos en:
                    <time datetime="2026-10-14T16:00:00+02:00">121d 23h 32m 17s</time>
                </div>
            </div>
            <div class="header-banner-frase">PLAZAS LIMITADAS</div>
            <div class="header-comprar">
                <a class="cta" href="/master-seo-tecnico/">Máster de SEO Técnico</a>
            </div>
        </div>

        <div class="absolute-navbar">
            <nav class="navbar-header" aria-label="Principal">
                <a href="/" id="main-link-home">
                    <!-- width/height sin "px": con unidad el navegador los
                         ignora y no reserva el hueco -> CLS.
                         Las medidas eran además 50x190, del revés para un logo -->
                    <div class="navbar-logo">
                        <img id="logo-carlos" src="<?php echo $tema; ?>/images/sanchez-white-seo.svg"
                             alt="Carlos Sánchez, logotipo" width="190" height="50">
                    </div>
                    <div class="nombre">PRUEBA WPO</div>
                </a>
                <ul id="main-navbar">
                    <li><a href="/">Inicio</a></li>
                    <li><a href="/servicios-seo/">Servicios</a></li>
                    <li><a href="/recursos/">Recursos</a></li>
                    <li><a href="/master-seo-tecnico/">Máster</a></li>
                    <li><a href="/sobre-mi/">Sobre mi</a></li>
                    <li id="helpme"><a href="/seo-avanzado/">Blog</a></li>
                </ul>
                <ul id="login-navbar">
                    <li><a href="/contacto/">Contacto</a></li>
                </ul>
            </nav>
        </div>

        <!-- ids corregidos: "mobile-nab" -> "mobile-nav", "menu-togle" -> "menu-toggle".
             Los onclick="" en línea se han sustituido por listeners en app.js
             y el div del toggle es ahora un <button> real -->
        <div id="mobile-nav" class="navbar-mobile">
            <nav class="mobile-ulbar-tcs" aria-label="Principal móvil">
                <div class="mobilepanel">
                    <button type="button" id="close-menu" class="close-menu" aria-label="Cerrar menú"></button>
                </div>
                <ul id="main-navbar-movile">
                    <li><a href="/">Inicio</a></li>
                    <li><a href="/servicios-seo/">Servicios</a></li>
                    <li><a href="/recursos/">Recursos</a></li>
                    <li class="masternavm"><a href="/master-seo-tecnico/">Máster</a></li>
                    <li><a href="/sobre-mi/">Sobre mi</a></li>
                    <li><a href="/seo-avanzado/">Blog</a></li>
                </ul>
            </nav>
        </div>

        <button type="button" id="menu-toggle" class="mobile-toggle"
                aria-controls="mobile-nav" aria-expanded="false" aria-label="Abrir menú"></button>
    </header>

    <!-- <x-layout> era un elemento sin definir que necesitaba display:block
         a mano. <main> hace lo mismo y aporta el landmark -->
    <main class="homelayout">

        <section id="cover">
            <div class="home-picture-mobile"></div>
            <div class="cover-fragment">
                <h1>La web de SEO técnico</h1>
                <div class="cover-text">
                    <p>El <strong>SEO técnico</strong> es la rama del SEO que utiliza la <strong>programación</strong> y el funcionamiento de la web a su favor para mejorar el rendimiento en los <strong>motores de búsqueda</strong>.</p>
                    <p>Esta especialidad del posicionamiento web tiene una <strong>base sólida</strong> donde todo tiene una <b>explicación lógica</b>.</p>
                </div>
                <div class="decorative"></div>
            </div>
        </section>

        <section id="simon-sinek">
            <div class="smsn-fragment">
                <div class="smsn-text">
                    <h2>El posicionamiento diferenciador</h2>
                    <!-- Aquí había <p> anidados dentro de otro <p> y sin cerrar:
                         el navegador cerraba el exterior por su cuenta y el DOM
                         no se parecía al código. Y dos <br></br>, que en HTML5
                         se parsean como DOS saltos de línea -->
                    <div class="cover-text">
                        <p>Si eres una persona a la que le mueve la curiosidad y siempre quiere saber el por qué de las cosas, es bastante posible que te apasione esta forma de trabajar el SEO.</p>
                        <p>El SEO es muy conocido por la palabra <b>depende</b>. Esto es debido a la gran cantidad de posibilidades que acaban conllevando a una consecuencia u otra.<br>
                            Un especialista en SEO Técnico conoce todas estas <b>variables</b> y de acuerdo a estas puede determinar cuales son las mejores implementaciones y estrategias.</p>
                        <p>El truco para saber trabajar en este apasionante mundo es tener unas bases sólidas y entender el funcionamiento desde el principio. No hay estrategias mágicas, ni es extrapolable lo que ha dicho un experto en SEO para todas las situaciones.</p>
                        <p>Sea para mejorar una web o para aprender más, déjame <a href="/contacto/">acompañarte en este proceso</a> y mostrarte lo reconfortante que puede llegar a ser el SEO Técnico.</p>
                    </div>
                    <div class="decorative"></div>
                </div>
                <div class="center-spacing"></div>
                <div class="smsn-img">
                    <!-- Primera imagen del documento: está en el pliegue o muy
                         cerca, así que NADA de loading="lazy" y sí fetchpriority.
                         width/height para reservar el hueco -->
                    <picture>
                        <source srcset="<?php echo $tema; ?>/images/home/carlos-seo.webp" type="image/webp">
                        <img class="static-img" src="<?php echo $tema; ?>/images/home/carlos-seo.png"
                             alt="Carlos Sánchez, consultor de SEO técnico"
                             width="600" height="400" fetchpriority="high" decoding="async">
                    </picture>
                </div>
            </div>

            <div class="smsn-fragment revert">
                <div class="smsn-text">
                    <h2>Especializarse en SEO Técnico</h2>
                    <div class="cover-text">
                        <p>Optimizar para SEO de forma técnica no consiste en tocar 4 plugins. Sino en entender la base de una web, comprender como rastrea un buscador y facilitarle su proceso para entender una web y su contenido.</p>
                        <p>Para trabajar en proyectos grandes hay que saber de tecnologías robustas y avanzadas ya que, CMS como WordPress a menudo son incapaces de soportarlos. <b>Se puede hacer SEO sin WordPress</b>.<br>
                            <a href="/seo-avanzado/">Con conocimientos avanzados</a>, se puede manejar y <strong>optimizar para SEO cualquier tipo de Web</strong>. Independientemente de la tecnología que se use.</p>
                        <p>Si quieres obtener buenos resultados en Marketing, no hagas exactamente lo mismo que los demás. Pero para poder actuar diferente con éxito, tienes que tener conocimiento.</p>
                    </div>
                    <div class="decorative"></div>
                </div>
                <div class="center-spacing"></div>
                <div class="smsn-img">
                    <picture>
                        <source srcset="<?php echo $tema; ?>/images/home/code-seo-laravel.webp" type="image/webp">
                        <img class="static-img" src="<?php echo $tema; ?>/images/home/code-seo-laravel.png"
                             alt="Código de SEO técnico en un editor"
                             width="600" height="400" loading="lazy" decoding="async">
                    </picture>
                </div>
            </div>

            <div class="smsn-fragment">
                <div class="smsn-text">
                    <h2>Mejorar en lo laboral</h2>
                    <div class="cover-text">
                        <p>El SEO técnico está en auge y ha venido para quedarse, actualmente <b>es un reto para las empresas encontrar un SEO Técnico</b>.</p>
                        <p>Esto me ha permitido <b>elegir</b> mis <b>condiciones de trabajo</b> a lo largo de mi recorrido laboral, no solo es un sector apasionante, si no que tiene mucha demanda.</p>
                        <p>La mayor parte de empresas necesitan SEOs que sepan trabajar con distintas tecnologías. Es un elemento diferenciador que se suele valorar.</p>
                        <p>En esta web tendrás toda la información necesaria a tu alcance y un <a href="/recursos/">conjunto de herramientas</a> para facilitarte las tareas.</p>
                    </div>
                    <div class="decorative"></div>
                </div>
                <div class="center-spacing"></div>
                <div class="smsn-img">
                    <!-- El <source> del WebP estaba comentado: esta tarjeta
                         servía el PNG a todo el mundo -->
                    <picture>
                        <source srcset="<?php echo $tema; ?>/images/home/datos-mejora.webp" type="image/webp">
                        <img class="static-img" src="<?php echo $tema; ?>/images/home/datos-mejora.png"
                             alt="Datos de un consultor de SEO técnico"
                             width="600" height="400" loading="lazy" decoding="async">
                    </picture>
                </div>
            </div>
        </section>

        <section id="masterme" class="freepad" data-nosnippet>
            <div class="center">
                <!-- Era <div class="heading heading2">: se veía como encabezado
                     pero no lo era ni para Google ni para un lector de pantalla -->
                <h2 class="heading2">No se te da mal el SEO Técnico</h2>
                <p><b>Te falta mi máster</b>. Accede a una formación avanzada que te permitirá aplicar e implementar SEO en cualquier tipo de WEB</p>
                <a class="cta" href="/master-seo-tecnico/">¡Accede al Máster de SEO Técnico!</a>
            </div>
        </section>

        <section class="grey-bg freepad">
            <h2>El SEO Técnico</h2>
            <p>El SEO Técnico es una rama o especialización avanzada del SEO, donde se funden los conocimientos de SEO y programación.</p>
            <p>Ciertos campos, como las correctas implementaciones de canonicals, <a href="/articulo/implementacion-hreflang/">hreflang</a>, <a href="/recursos/redirecciones-servidor/">redirecciones</a>, sitemaps, <a href="/seo-avanzado/metaetiquetas/">metaetiquetas</a>, <a href="/articulo/hacer-robots-txt/">robots.txt</a> y <a href="/seo-avanzado/rendimiento-velocidad/">mejoras del rendimiento y velocidad de la web</a> entre otros. Quedan en terreno de nadie cuando el SEO no sabe realizarlas y el programador no entiende las necesidades de los motores de búsqueda.</p>
            <p>Aquí es donde entra en juego el SEO Técnico, especialidad cada vez más demandada por todo tipo de empresas. Especialmente las más potentes.</p>
            <p>Si bien un <strong>programador</strong> tiene amplios conocimientos en programación, sus conocimientos suelen ir enfocados en la <strong>funcionalidad</strong>, pero no en el entendimiento de una web de cara a los motores de búsqueda. Por otro lado un <strong>SEO</strong> genérico suele tener problemas a la hora de entender como funcionan los rastreadores o las propias webs o realizar cualquier tipo de <strong>implementación</strong> por esa carencia de conocimiento técnico.</p>
            <p>Un SEO para considerarse SEO Técnico debe entender los fundamentos de una web, como funciona esta y como funcionan los rastreadores. Debe saber como funcionan distintas tecnologías y sacar su potencial y adaptarlo para obtener los mejores resultados en los motores de búsqueda.</p>
            <p>Debido a la gran curva de aprendizaje que tiene esta rama del SEO, no es habitual encontrar SEOs con esta especialidad, por lo que sigue habiendo una gran demanda de SEO insatisfecha tanto en España como en el resto del mundo. Ya que la polivalencia y eficacia que tiene un SEO Técnico es deseada en cualquier lugar. No solo por los resultados que se consiguen, sino por la velocidad con la que se sacan las tareas hacia delante.</p>

            <h2>Tu SEO de referencia</h2>
            <p><a title="Por si te preguntas quien soy" href="/sobre-mi/">Soy PRUEBA WPO</a>, conmigo aprenderás a hacer SEO desde la programación, entendiendo cual es cada acción desde la base y por qué funciona.</p>
            <p>Tengo una amplia experiencia posicionando en gran variedad de empresas internacionales, con todo tipo de tecnologías y peculiaridades.</p>
            <p>En esta web tengo la intención de ahorrarte la curva de aprendizaje que tuve que recorrer para destacar en este mundo. Quiero enseñarte lo alucinante que puede ser esto.</p>
        </section>

        <!-- Las doce portadas eran background-image en línea:
             no se pueden diferir, el preload scanner no las ve, no tienen alt
             y no existen para Google Imágenes. Ahora son <img> de verdad.
             Los divs .posts-h2 pasan a <h3>, dentro del <h2> de la sección.
             Ajustar width/height a las dimensiones reales de las portadas. -->
        <section class="homeposts">
            <h2>Aprende SEO On-Page</h2>
            <div class="homepostdisplayer">

                <article class="excerpt-post" id="post-2501">
                    <a href="/noticia/topic-authority/">
                        <div class="posts-picture">
                            <img src="/wp-content/uploads/cover-google-updates.jpg" alt="Actualizaciones de Google"
                                 width="400" height="225" loading="lazy" decoding="async">
                            <div class="masinfo-text"><span class="secondary">&gt;</span> Clic para + info</div>
                        </div>
                        <h3 class="posts-h2">Topic Authority</h3>
                        <div class="entry">Como tiene en cuenta Google la autoridad de una web sobre cierto contenido</div>
                    </a>
                </article>

                <article class="excerpt-post" id="post-2412">
                    <a href="/articulo/sintaxis-de-urls/">
                        <div class="posts-picture">
                            <img src="/wp-content/uploads/cover-sintaxis-url.jpg" alt="Sintaxis de una URL"
                                 width="400" height="225" loading="lazy" decoding="async">
                            <div class="masinfo-text"><span class="secondary">&gt;</span> Clic para + info</div>
                        </div>
                        <h3 class="posts-h2">Sintaxis de URLs</h3>
                        <div class="entry">La URL es la base del posicionamiento, puesto que los motores de búsqueda son un directorio/buscador de URLs</div>
                    </a>
                </article>

                <article class="excerpt-post" id="post-2417">
                    <a href="/articulo/data-uri/">
                        <div class="posts-picture">
                            <img src="/wp-content/uploads/cover-data-uri.jpg" alt="Data URI"
                                 width="400" height="225" loading="lazy" decoding="async">
                            <div class="masinfo-text"><span class="secondary">&gt;</span> Clic para + info</div>
                        </div>
                        <h3 class="posts-h2">Data URI</h3>
                        <div class="entry">Un Data URI es un identificador único de distintos tipos de archivos que no requieren estar alojados en un servidor para ser cargados</div>
                    </a>
                </article>

                <article class="excerpt-post" id="post-2326">
                    <a href="/articulo/x-default/">
                        <div class="posts-picture">
                            <img src="/wp-content/uploads/cover-x-default.jpg" alt="Valor x-default del atributo hreflang"
                                 width="400" height="225" loading="lazy" decoding="async">
                            <div class="masinfo-text"><span class="secondary">&gt;</span> Clic para + info</div>
                        </div>
                        <h3 class="posts-h2">Valor x-default en el atributo hreflang</h3>
                        <div class="entry">Mejorar la experiencia del usuario y motores de búsqueda en proyectos internacionales con el x-default</div>
                    </a>
                </article>

                <article class="excerpt-post" id="post-2383">
                    <a href="/noticia/googleother-user-agent/">
                        <div class="posts-picture">
                            <img src="/wp-content/uploads/cover-dejson-kayak.jpg" alt="User agent GoogleOther"
                                 width="400" height="225" loading="lazy" decoding="async">
                            <div class="masinfo-text"><span class="secondary">&gt;</span> Clic para + info</div>
                        </div>
                        <h3 class="posts-h2">GoogleOther, un nuevo user agent</h3>
                        <div class="entry">Un nuevo user-agent de Google con un misterioso propósito</div>
                    </a>
                </article>

                <article class="excerpt-post" id="post-2344">
                    <a href="/articulo/elementor-problemas/">
                        <div class="posts-picture">
                            <img src="/wp-content/uploads/cover-elementor.jpg" alt="Elementor y el SEO técnico"
                                 width="400" height="225" loading="lazy" decoding="async">
                            <div class="masinfo-text"><span class="secondary">&gt;</span> Clic para + info</div>
                        </div>
                        <h3 class="posts-h2">Elementor en SEO Técnico</h3>
                        <div class="entry">Una pequeña parte de los problemas que suele dar Elementor en el SEO Técnico</div>
                    </a>
                </article>

                <article class="excerpt-post" id="post-2334">
                    <a href="/articulo/url-mayusculas-y-minusculas/">
                        <div class="posts-picture">
                            <img src="/wp-content/uploads/cover-upperlowercase.jpg" alt="Mayúsculas y minúsculas en una URL"
                                 width="400" height="225" loading="lazy" decoding="async">
                            <div class="masinfo-text"><span class="secondary">&gt;</span> Clic para + info</div>
                        </div>
                        <h3 class="posts-h2">URL mayúsculas y minúsculas</h3>
                        <div class="entry">Gestionar las mayúsculas en una URL</div>
                    </a>
                </article>

                <?php /* Fuera la tarjeta #post-2542 "sdfds" / "dsfdsdsf": contenido de pruebas publicado */ ?>

                <!-- Era id="load-more", repetido en los dos botones -->
                <a class="load-more ctaflot ctaofusqued cta" href="/seo-avanzado/">Ver más artículos</a>
            </div>

            <!-- Era <h3>: como las tarjetas de arriba ahora son h3, esto sube a h2 -->
            <h2>Curiosidades en el mundo SEO</h2>
            <div class="homepostdisplayer">

                <article class="excerpt-post" id="post-1210">
                    <a href="/curiosidades/tipos-de-imagenes/">
                        <div class="posts-picture">
                            <img src="/wp-content/uploads/cover-decorative-image.jpg" alt="Tipos de imágenes en SEO"
                                 width="400" height="225" loading="lazy" decoding="async">
                            <div class="masinfo-text"><span class="secondary">&gt;</span> Clic para + info</div>
                        </div>
                        <h3 class="posts-h2">Tipos de imágenes en el SEO</h3>
                        <div class="entry">Como identificar las imágenes dentro del SEO según su importancia</div>
                    </a>
                </article>

                <article class="excerpt-post" id="post-758">
                    <a href="/curiosidades/como-inserto-el-sitemap-dentro-del-robots-txt/">
                        <div class="posts-picture">
                            <img src="/wp-content/uploads/cover-cat-robots-sitemaps.jpg" alt="Sitemap dentro del robots.txt"
                                 width="400" height="225" loading="lazy" decoding="async">
                            <div class="masinfo-text"><span class="secondary">&gt;</span> Clic para + info</div>
                        </div>
                        <h3 class="posts-h2">¿Cómo inserto el sitemap dentro del robots.txt?</h3>
                        <div class="entry">Como enlazar el sitemap dentro del robots.txt</div>
                    </a>
                </article>

                <article class="excerpt-post" id="post-2485">
                    <a href="/curiosidades/desindexar-paginas-chino-japones/">
                        <div class="posts-picture">
                            <img src="/wp-content/uploads/cover-japa-ch.jpg" alt="Páginas hackeadas en chino y japonés"
                                 width="400" height="225" loading="lazy" decoding="async">
                            <div class="masinfo-text"><span class="secondary">&gt;</span> Clic para + info</div>
                        </div>
                        <h3 class="posts-h2">Solucionar hack de páginas en chino o japonés</h3>
                        <div class="entry">Solucionar la indexación de una web hackeada</div>
                    </a>
                </article>

                <article class="excerpt-post" id="post-904">
                    <a href="/curiosidades/critica-limit-login/">
                        <div class="posts-picture">
                            <img src="/wp-content/uploads/cover-limit-login.jpg" alt="Plugin Limit Login Attempts"
                                 width="400" height="225" loading="lazy" decoding="async">
                            <div class="masinfo-text"><span class="secondary">&gt;</span> Clic para + info</div>
                        </div>
                        <h3 class="posts-h2">Por qué no usar Limit Login Attempts</h3>
                        <div class="entry">Limit Login Attempts introduce registros falsos para que te asustes y compres la versión Premium.</div>
                    </a>
                </article>

                <!-- Estaba FUERA del .homepostdisplayer, después de cerrarlo -->
                <a class="load-more ctaflot ctaofusqued cta" href="/seo-curiosidades/">Ver más curiosidades</a>
            </div>
        </section>
    </main>

    <footer id="main-footer" class="black-bg">
        <div class="container less-size">
            <section id="primaryfooter" class="row-elements">

                <div class="footer-column">
                    <div class="title-footer">
                        <img src="<?php echo $tema; ?>/images/complements/tanit-footer.svg"
                             alt="Carlos Sánchez, SEO manager" width="52" height="80" loading="lazy">
                    </div>
                    <div class="footer-text frase-footer">
                        <!-- Aquí había otro </br> -->
                        <p class="light-gray-color center">PRUEBA WPO<br>El SEO Técnico</p>
                    </div>
                </div>

                <div class="footer-column">
                    <div class="title-column titlesfooter">CATEGORÍAS</div>
                    <div class="footer-text">
                        <nav aria-label="Categorías">
                            <ul class="footer-list">
                                <li><a href="/seo-curiosidades/">Curiosidades</a></li>
                                <li><a href="/seo-avanzado/seo-avanzado/">Blog de SEO avanzado</a></li>
                                <li><a href="/seo-avanzado/enlazado/">Enlazado</a></li>
                                <li><a href="/seo-avanzado/seo-internacional/">Internacional</a></li>
                                <li><a href="/seo-avanzado/metaetiquetas/">Metaetiquetas</a></li>
                                <li><a href="/seo-avanzado/multimedia/">Multimedia</a></li>
                                <li><a href="/seo-avanzado/rastreo/">Rastreo</a></li>
                                <li><a href="/seo-avanzado/servidores/">Servidores</a></li>
                                <li><a href="/seo-avanzado/tecnologias/">Tecnologías</a></li>
                                <li><a href="/seo-avanzado/rendimiento-velocidad/">WPO</a></li>
                                <li><a href="/noticias/">Noticias</a></li>
                            </ul>
                        </nav>
                    </div>
                </div>

                <div class="footer-column">
                    <div class="title-column titlesfooter">ÚLTIMOS POSTS</div>
                    <div class="footer-text">
                        <nav aria-label="Últimos posts">
                            <!-- Los title="" que repetían el extracto entero no
                                 aportan nada: el texto del enlace ya lo dice.
                                 Y fuera el post de pruebas "sdfds" -->
                            <ul class="footer-list">
                                <li><a href="/noticia/topic-authority/">Topic Authority</a></li>
                                <li><a href="/curiosidades/desindexar-paginas-chino-japones/">Solucionar hack de páginas en chino o japonés</a></li>
                                <li><a href="/articulo/sintaxis-de-urls/">Sintaxis de URLs</a></li>
                                <li><a href="/articulo/data-uri/">Data URI</a></li>
                                <li><a href="/articulo/x-default/">Valor x-default en el atributo hreflang</a></li>
                                <li><a href="/noticia/googleother-user-agent/">GoogleOther, un nuevo user agent</a></li>
                                <li><a href="/articulo/elementor-problemas/">Elementor en SEO Técnico</a></li>
                                <li><a href="/articulo/url-mayusculas-y-minusculas/">URL mayúsculas y minúsculas</a></li>
                                <li><a href="/curiosidades/microsoft-clarity/">Microsoft Clarity</a></li>
                            </ul>
                        </nav>
                    </div>
                </div>

                <div class="footer-column">
                    <div class="title-footer">
                        <div class="title-column titlesfooter">ACTUALÍZAME</div>
                    </div>
                    <div class="newsletter-monhly">Subscríbete para recibir <strong>un solo e-mail mensual</strong> con las noticias más importantes y destacadas del SEO.</div>
                    <div class="email">
                        <!-- El <style> que había aquí dentro está en diferido.css.
                             Los estilos en línea (uno de ellos comentado:
                             style="/*display:none*/") pasan a clases.
                             El nonce y la URL de admin-ajax salen con
                             wp_localize_script, no en un <script> del <head>
                             apuntando a sanchezdonate.test -->
                        <form id="sib_signup_form_2" method="post" class="sib_signup_form">
                            <div class="sib_loader is-hidden">
                                <img src="/wp-includes/images/spinner.gif" alt="" width="16" height="16">
                            </div>
                            <input type="hidden" name="sib_form_action" value="subscribe_form_submit">
                            <input type="hidden" name="sib_form_id" value="2">
                            <input type="hidden" name="sib_form_alert_notice" value="¡Por favor! Pon tu correo">
                            <?php /* wp_nonce_field( 'sib_subscribe', 'sib_security' ); */ ?>
                            <div class="sib_signup_box_inside_2">
                                <div class="sib_msg_disp is-hidden"></div>
                                <label class="screen-reader-text" for="sib-email-2">Email</label>
                                <input type="email" id="sib-email-2" class="sib-email-area" placeholder="Email" name="email" required>
                                <div class="send-icon">
                                    <button type="submit" class="sib-default-btn" name="submit" aria-label="Suscribirse">&#10140;</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

            </section>
        </div>

        <!-- rel="noreferrer, nofollow" con coma: rel es una lista separada
             por ESPACIOS, así que los dos valores se ignoraban. En los siete.
             Fuera también onclick="analyticsLinkedIn()", función que no está
             definida en ninguna parte del documento -->
        <div class="rrss-footer">
            <a href="https://www.linkedin.com/in/carlos-sanchez-donate/" target="_blank" rel="noopener noreferrer nofollow">
                <img class="rrss-picture" src="<?php echo $tema; ?>/images/rrss/linkedin.svg" alt="LinkedIn" width="50" height="50" loading="lazy">
                <div class="single-rrss">LinkedIn</div>
            </a>
            <a href="https://youtu.be/ySK1GlufiTg" target="_blank" rel="noopener noreferrer nofollow">
                <img class="rrss-picture" src="<?php echo $tema; ?>/images/rrss/yt.svg" alt="YouTube" width="50" height="50" loading="lazy">
                <div class="single-rrss">Youtube</div>
            </a>
            <a href="https://discord.gg/jgzsXYbwGd" target="_blank" rel="noopener noreferrer nofollow">
                <img class="rrss-picture" src="<?php echo $tema; ?>/images/rrss/discord.svg" alt="Discord" width="50" height="50" loading="lazy">
                <div class="single-rrss">Discord</div>
            </a>
            <a href="https://www.tiktok.com/@elseotecnico" target="_blank" rel="noopener noreferrer nofollow">
                <img class="rrss-picture" src="<?php echo $tema; ?>/images/rrss/tik-tok.svg" alt="TikTok" width="50" height="50" loading="lazy">
                <div class="single-rrss">Tik Tok</div>
            </a>
            <a href="https://www.instagram.com/elseotecnico/" target="_blank" rel="noopener noreferrer nofollow">
                <img class="rrss-picture" src="<?php echo $tema; ?>/images/rrss/instagram.svg" alt="Instagram" width="50" height="50" loading="lazy">
                <div class="single-rrss">Instagram</div>
            </a>
            <a href="https://www.twitch.tv/carlos_sanchez_donate" target="_blank" rel="noopener noreferrer nofollow">
                <img class="rrss-picture" src="<?php echo $tema; ?>/images/rrss/twitch.svg" alt="Twitch" width="50" height="50" loading="lazy">
                <div class="single-rrss">Twitch</div>
            </a>
            <a href="https://twitter.com/SEO_Tecnico" target="_blank" rel="noopener noreferrer nofollow">
                <img class="rrss-picture" src="<?php echo $tema; ?>/images/rrss/tw.svg" alt="Twitter" width="50" height="50" loading="lazy">
                <div class="single-rrss">Twitter</div>
            </a>
            <?php /* Aquí había un </a> de más, y arriba un </section> huérfano
                     justo antes de </footer> */ ?>
        </div>

        <!-- Esta sección estaba FUERA del <footer>, como hermana suya -->
        <section id="post-footer" class="dark-gray-bg">
            <div class="container">
                <div class="post-footer">
                    <nav aria-label="Legal">
                        <ul>
                            <li><a href="/aviso-legal/" rel="nofollow">Aviso legal</a></li>
                            <li><a href="/politica-de-cookies/" rel="nofollow">Política de cookies</a></li>
                            <li><a href="/politica-privacidad/" rel="nofollow">Política de privacidad</a></li>
                            <li><a href="/sitemap-index.xml">Sitemap SEO</a></li>
                            <li><a href="https://master.sanchezdonate.com/" target="_blank" rel="noopener noreferrer nofollow">Aula virtual</a></li>
                        </ul>
                    </nav>
                </div>
            </div>
        </section>
    </footer>

</body>

</html>
