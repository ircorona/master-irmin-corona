<?php
/**
 * Datos estructurados automáticos del tema — clase 07 (automatización).
 *
 * Se incluye desde header.php, justo detrás de metas-seo.php, y emite JSON-LD
 * distinto según lo que sea la página:
 *
 *   - Entrada de la categoría "cars"  ->  Car   (subtipo de Vehicle -> Product)
 *   - Cualquier otro contenido        ->  BlogPosting
 *   - Todas las páginas               ->  Organization
 *
 * Los datos del coche salen de los MISMOS campos ACF que single.php pinta en la
 * tabla "Ficha Técnica" (hp, price, fuel, brand). Una sola fuente: si mañana
 * cambia el precio en el editor, cambian a la vez la tabla y el marcado.
 *
 * Diferencia con el ejemplo de clase: allí los valores se echan directamente
 * dentro del JSON ("headline": "<?php the_title(); ?>"). Eso funciona hasta que
 * un título lleva comillas, un salto de línea o una barra invertida — y entonces
 * rompe el bloque ENTERO, en silencio. Aquí se monta un array de PHP y se
 * serializa con wp_json_encode(), que no puede generar JSON inválido.
 *
 * @package Asdrubal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'asdrubal_schema_campo' ) ) :
	/**
	 * Lee un campo ACF sin romper si ACF está desactivado.
	 *
	 * @param string $nombre  Nombre del campo.
	 * @param mixed  $objeto  ID de post o término.
	 * @return mixed Valor del campo, o '' si no hay ACF / no hay valor.
	 */
	function asdrubal_schema_campo( $nombre, $objeto = null ) {
		if ( ! function_exists( 'get_field' ) ) {
			return '';
		}
		$valor = get_field( $nombre, $objeto );
		return ( null === $valor || false === $valor ) ? '' : $valor;
	}
endif;

if ( ! function_exists( 'asdrubal_schema_imagen_url' ) ) :
	/**
	 * Normaliza el valor de un campo imagen de ACF a una URL.
	 * ACF devuelve array, ID o URL según cómo esté configurado el campo.
	 *
	 * @param mixed $valor Valor del campo.
	 * @return string URL o cadena vacía.
	 */
	function asdrubal_schema_imagen_url( $valor ) {
		if ( empty( $valor ) ) {
			return '';
		}
		if ( is_array( $valor ) ) {
			return isset( $valor['url'] ) ? (string) $valor['url'] : '';
		}
		if ( is_numeric( $valor ) ) {
			return (string) wp_get_attachment_image_url( (int) $valor, 'full' );
		}
		return (string) $valor;
	}
endif;

if ( ! function_exists( 'asdrubal_schema_numero' ) ) :
	/**
	 * Deja un valor listo para JSON: número limpio, punto decimal, sin símbolos.
	 *
	 * Los campos los rellena una persona, así que llegan como "45.900 $", "45,900"
	 * o "29.95". Hay que decidir si el separador es de millares o decimal, porque
	 * confundirlos multiplica el precio por mil:
	 *   - Si aparecen los dos, el de más a la derecha es el decimal.
	 *   - Si solo hay uno y va seguido de exactamente 3 cifras, es de millares.
	 *   - En cualquier otro caso, es decimal.
	 *
	 * "45.900 $" -> 45900.0 ; "29,95" -> 29.95 ; "1.234.567" -> 1234567.0 ; "" -> null
	 *
	 * @param mixed $valor Valor bruto del campo.
	 * @return float|null
	 */
	function asdrubal_schema_numero( $valor ) {
		if ( is_array( $valor ) || '' === $valor || null === $valor ) {
			return null;
		}

		$limpio = preg_replace( '/[^0-9.,-]/', '', (string) $valor );
		if ( '' === $limpio ) {
			return null;
		}

		$pos_punto = strrpos( $limpio, '.' );
		$pos_coma  = strrpos( $limpio, ',' );

		if ( false !== $pos_punto && false !== $pos_coma ) {
			// Los dos separadores: manda el último.
			$decimal   = $pos_punto > $pos_coma ? '.' : ',';
			$millares  = $pos_punto > $pos_coma ? ',' : '.';
			$limpio    = str_replace( $millares, '', $limpio );
			$limpio    = str_replace( $decimal, '.', $limpio );
		} elseif ( false !== $pos_punto || false !== $pos_coma ) {
			$separador = false !== $pos_punto ? '.' : ',';
			$posicion  = false !== $pos_punto ? $pos_punto : $pos_coma;
			$decimales = strlen( $limpio ) - $posicion - 1;

			if ( 3 === $decimales && 1 === substr_count( $limpio, $separador ) ) {
				$limpio = str_replace( $separador, '', $limpio );   // millares: 45.900
			} elseif ( substr_count( $limpio, $separador ) > 1 ) {
				$limpio = str_replace( $separador, '', $limpio );   // millares: 1.234.567
			} else {
				$limpio = str_replace( $separador, '.', $limpio );  // decimal: 29,95
			}
		}

		return is_numeric( $limpio ) ? (float) $limpio : null;
	}
endif;

if ( ! function_exists( 'asdrubal_schema_print' ) ) :
	/**
	 * Imprime un bloque <script type="application/ld+json"> desde un array.
	 *
	 * wp_json_encode() se encarga de las comillas, las comas y los escapes: por
	 * construcción no puede salir una coma final ni una comilla sin cerrar.
	 * El str_replace final evita que un texto que contenga "</script>" cierre la
	 * etiqueta y reviente la página ("<\/" es JSON perfectamente válido).
	 *
	 * @param array $datos Estructura a serializar.
	 */
	function asdrubal_schema_print( array $datos ) {
		$datos = array_filter(
			$datos,
			static function ( $valor ) {
				return null !== $valor && '' !== $valor && array() !== $valor;
			}
		);

		$json = wp_json_encode( $datos, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
		if ( false === $json ) {
			return;
		}

		echo "\n" . '<script type="application/ld+json">' . "\n";
		echo str_replace( '</', '<\/', $json ) . "\n";
		echo '</script>' . "\n";
	}
endif;

/* -------------------------------------------------------------------------
 * Contexto de la página (mismo criterio que metas-seo.php)
 * ---------------------------------------------------------------------- */

$schema_term = ( is_category() || is_tag() || is_tax() ) ? get_queried_object() : get_the_ID();

// URL canónica: si hay campo canonical se respeta, y así el @id del marcado y el
// <link rel="canonical"> apuntan siempre al mismo sitio. Nunca a $_SERVER.
$schema_canonical = asdrubal_schema_campo( 'canonical', $schema_term );
if ( ! $schema_canonical ) {
	$schema_canonical = is_singular() ? get_permalink() : home_url( add_query_arg( array() ) );
}
$schema_canonical = strtok( $schema_canonical, '?' ); // fuera query string.

/* -------------------------------------------------------------------------
 * 1. Contenido individual: Car para los coches, BlogPosting para lo demás
 * ---------------------------------------------------------------------- */

if ( is_singular() ) :

	// Imagen: campo del coche -> imagen destacada -> og_image -> imagen del tema.
	$schema_imagen = asdrubal_schema_imagen_url( asdrubal_schema_campo( 'imagencoche', $schema_term ) );
	if ( ! $schema_imagen && has_post_thumbnail() ) {
		$schema_imagen = (string) get_the_post_thumbnail_url( null, 'full' );
	}
	if ( ! $schema_imagen ) {
		$schema_imagen = asdrubal_schema_imagen_url( asdrubal_schema_campo( 'og_image', $schema_term ) );
	}
	if ( ! $schema_imagen ) {
		$schema_imagen = get_template_directory_uri() . '/asdrubal.jpg';
	}

	if ( in_category( 'cars' ) ) :

		/*
		 * Car es subtipo de Vehicle, que a su vez es subtipo de Product: hereda
		 * name, image, brand y offers, y añade lo específico del vehículo.
		 * Cada propiedad sale del mismo campo ACF que se ve en la tabla de
		 * single.php -> nada marcado que el usuario no pueda leer en la página.
		 */
		$coche_hp     = asdrubal_schema_numero( asdrubal_schema_campo( 'hp', $schema_term ) );
		$coche_precio = asdrubal_schema_numero( asdrubal_schema_campo( 'price', $schema_term ) );
		$coche_fuel   = asdrubal_schema_campo( 'fuel', $schema_term );
		$coche_marca  = asdrubal_schema_campo( 'brand', $schema_term );

		/*
		 * Descripción con los mismos datos que la tabla "Ficha Técnica". Se monta
		 * solo con los campos que existen: una descripción con huecos ("- HP, ,")
		 * es peor que una descripción corta. No se copia literalmente la meta
		 * description de metas-seo.php porque esa repite la marca que ya va en el
		 * título ("a Alfa Romeo Alfa Romeo Giulia").
		 */
		$coche_partes = array_filter(
			array(
				null !== $coche_hp ? $coche_hp . ' HP' : '',
				$coche_fuel ? (string) $coche_fuel : '',
				null !== $coche_precio ? '$' . number_format( $coche_precio, 0, '.', ',' ) : '',
			)
		);
		$coche_desc   = wp_strip_all_tags( get_the_title() );
		if ( $coche_partes ) {
			$coche_desc .= ' - ' . implode( ', ', $coche_partes );
		}

		$coche = array(
			'@context'    => 'https://schema.org',
			'@type'       => 'Car',
			'@id'         => $schema_canonical . '#coche',
			'name'        => wp_strip_all_tags( get_the_title() ),
			'description' => $coche_desc,
			'image'       => array( $schema_imagen ),
			'url'         => $schema_canonical,
		);

		if ( $coche_marca ) {
			$coche['brand'] = array(
				'@type' => 'Brand',
				'name'  => wp_strip_all_tags( (string) $coche_marca ),
			);
		}

		if ( $coche_fuel ) {
			$coche['fuelType'] = wp_strip_all_tags( (string) $coche_fuel );
		}

		if ( null !== $coche_hp ) {
			// La potencia va como QuantitativeValue con unidad, no como "320 HP".
			$coche['vehicleEngine'] = array(
				'@type'       => 'EngineSpecification',
				'enginePower' => array(
					'@type'    => 'QuantitativeValue',
					'value'    => $coche_hp,
					'unitCode' => 'BHP', // código UN/CEFACT de caballo de potencia.
				),
			);
		}

		if ( null !== $coche_precio ) {
			$coche['offers'] = array(
				'@type'         => 'Offer',
				'url'           => $schema_canonical,
				'price'         => number_format( $coche_precio, 2, '.', '' ), // sin símbolo ni coma
				'priceCurrency' => 'USD',                                      // ISO 4217; la ficha muestra $
				'availability'  => 'https://schema.org/InStock',               // URL completa
				'itemCondition' => 'https://schema.org/UsedCondition',
			);
		}

		asdrubal_schema_print( $coche );

	else :

		// Contenido editorial normal: los datos salen del post, no de campos sueltos.
		$autor_id = (int) get_post_field( 'post_author', get_the_ID() );

		asdrubal_schema_print(
			array(
				'@context'         => 'https://schema.org',
				'@type'            => 'BlogPosting',
				'@id'              => $schema_canonical . '#article',
				'mainEntityOfPage' => array(
					'@type' => 'WebPage',
					'@id'   => $schema_canonical,
				),
				'headline'         => mb_substr( wp_strip_all_tags( get_the_title() ), 0, 110 ),
				'description'      => wp_strip_all_tags( (string) asdrubal_schema_campo( 'meta_description', $schema_term ) ),
				'image'            => array( $schema_imagen ),
				'datePublished'    => get_the_date( 'c' ),  // ISO 8601 CON zona horaria
				'dateModified'     => get_the_modified_date( 'c' ),
				'author'           => array(
					'@type' => 'Person',
					'name'  => get_the_author_meta( 'display_name', $autor_id ),
					'url'   => get_author_posts_url( $autor_id ),
				),
				'inLanguage'       => get_bloginfo( 'language' ),
			)
		);

	endif;

endif;

/* -------------------------------------------------------------------------
 * 2. Organization — en todas las páginas
 * ---------------------------------------------------------------------- */

$schema_logo_id  = (int) get_theme_mod( 'custom_logo' );
$schema_logo_url = $schema_logo_id ? wp_get_attachment_image_url( $schema_logo_id, 'full' ) : '';
if ( ! $schema_logo_url ) {
	$schema_logo_url = get_template_directory_uri() . '/asdrubal.jpg';
}

asdrubal_schema_print(
	array(
		'@context'    => 'https://schema.org',
		'@type'       => 'Organization',
		'@id'         => home_url( '/#organization' ),
		'name'        => wp_strip_all_tags( get_bloginfo( 'name' ) ),
		'description' => wp_strip_all_tags( get_bloginfo( 'description' ) ),
		'url'         => home_url( '/' ),
		'logo'        => $schema_logo_url,
	)
);
