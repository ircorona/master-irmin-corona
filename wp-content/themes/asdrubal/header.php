<!DOCTYPE html>
<html <?php language_attributes(); ?>>
    <head>
        
        <?php  

        include 'components/metas-seo.php';

        // Datos estructurados automáticos (clase 07). get_template_part lo carga
        // en su propio ámbito, así no comparte variables con metas-seo.php.
        get_template_part('components/datos-estructurados');

        wp_head();
        ?>
    </head>
    <body <?php body_class(); ?>>
        <header>
            <nav>
                <?php
                // Menú dinámico de WordPress. Si aún no has creado un menú en
                // Apariencia > Menús, el fallback (wp_page_menu) lista tus páginas reales.
                wp_nav_menu(array(
                    'theme_location' => 'primary',
                    'container'      => false,
                    'fallback_cb'    => 'wp_page_menu',
                    'show_home'      => true,
                ));
                ?>
            </nav>
        </header>