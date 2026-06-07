<?php
/*
Plugin Name: Irmin Sitemap Template
Plugin URI: https://climbthesearches.com
Description: Añade una plantilla de página "Sitemap (Irmin)" que genera un mapa del sitio en HTML con las páginas y entradas publicadas. Ejercicio clase 11 — plugin propio.
Author: Irmin Corona
Author URI: https://climbthesearches.com
Version: 1.0.0
License: GPLv2 or later
Text Domain: irmin-sitemap-template
*/

// Seguridad: si se accede al fichero directamente (no a través de WordPress), salir.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registra una plantilla de página virtual y carga su fichero cuando se usa.
 *
 * En WordPress 4.7+ basta con dos filtros:
 *   - theme_page_templates : añade la plantilla al desplegable del editor.
 *   - template_include     : carga nuestro fichero si la página la tiene asignada.
 * (El truco de caché de versiones <4.7 ya no hace falta.)
 */
class Irmin_Sitemap_Template {

	/** Clave virtual de la plantilla (no es un fichero del tema). */
	const TEMPLATE_SLUG = 'irmin-sitemap';

	public function __construct() {
		add_filter( 'theme_page_templates', array( $this, 'register_template' ) );
		add_filter( 'template_include', array( $this, 'load_template' ) );
	}

	/**
	 * Añade la plantilla a "Atributos de página > Plantilla".
	 *
	 * @param array $templates Plantillas ya registradas por el tema.
	 * @return array
	 */
	public function register_template( $templates ) {
		$templates[ self::TEMPLATE_SLUG ] = __( 'Sitemap (Irmin)', 'irmin-sitemap-template' );
		return $templates;
	}

	/**
	 * Si la página usa nuestra plantilla, devuelve el fichero del plugin.
	 *
	 * @param string $template Ruta de plantilla que iba a usar WordPress.
	 * @return string
	 */
	public function load_template( $template ) {
		if ( is_page() ) {
			$assigned = get_page_template_slug( get_queried_object_id() );
			if ( self::TEMPLATE_SLUG === $assigned ) {
				$custom = plugin_dir_path( __FILE__ ) . 'templates/sitemap.php';
				if ( file_exists( $custom ) ) {
					return $custom;
				}
			}
		}
		return $template;
	}
}

new Irmin_Sitemap_Template();
