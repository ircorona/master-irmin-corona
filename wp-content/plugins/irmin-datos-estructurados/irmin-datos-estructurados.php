<?php
/*
Plugin Name: Irmin Datos Estructurados
Plugin URI: https://climbthesearches.com
Description: Genera datos estructurados JSON-LD desde los datos reales del sitio: BlogPosting (entradas), FAQPage (preguntas visibles del contenido), Product (shortcode [irmin_producto]), más BreadcrumbList y Organization/WebSite. Ejercicio clase 06 — crear código.
Author: Irmin Corona
Author URI: https://climbthesearches.com
Version: 1.0.0
License: GPLv2 or later
Text Domain: irmin-datos-estructurados
*/

// Seguridad: sin acceso directo al fichero.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Datos estructurados JSON-LD generados, nunca escritos a mano.
 *
 * Regla que gobierna todo el plugin: cada propiedad sale de la MISMA fuente que
 * pinta el HTML (get_the_title, get_the_date, el propio post_content...). Así es
 * imposible que el marcado se desincronice de la página, y es imposible marcar
 * contenido que el usuario no ve — que es lo que Google considera spam.
 *
 * Tipos que emite:
 *   1. BlogPosting    -> en entradas individuales, con los datos del post.
 *   2. FAQPage        -> en cualquier contenido cuyos encabezados sean preguntas.
 *   3. Product        -> donde se use el shortcode [irmin_producto], que además
 *                        pinta la ficha visible con esos mismos datos.
 *   + BreadcrumbList  -> migas de pan de la URL actual.
 *   + Organization / WebSite -> identidad del sitio, en todas las páginas.
 *
 * Nada se escribe con echo de cadenas: todo pasa por wp_json_encode(), que no
 * puede generar una coma final ni una comilla sin escapar (clase 05).
 */
class Irmin_Datos_Estructurados {

	/** @@id de la organización, para referenciarla desde el resto de entidades. */
	private $org_id;

	/** Datos de los productos declarados con el shortcode en esta página. */
	private $productos = array();

	public function __construct() {
		$this->org_id = home_url( '/#organization' );

		// El <head> es el sitio habitual del marcado.
		add_action( 'wp_head', array( $this, 'print_sitio' ), 5 );
		add_action( 'wp_head', array( $this, 'print_article' ), 6 );
		add_action( 'wp_head', array( $this, 'print_faq' ), 7 );
		add_action( 'wp_head', array( $this, 'print_breadcrumbs' ), 8 );

		// Product va en el FOOTER, y a propósito (recomendación de clase: cuanto más
		// abajo, mejor). Dos motivos: el <head> se sirve primero y no conviene
		// retrasarlo con marcado pesado de fichas, y el shortcode se ejecuta dentro
		// de the_content, o sea DESPUÉS de wp_head — desde el head, sus datos aún no
		// existen. Google lee el JSON-LD igual en <body> que en <head>.
		add_shortcode( 'irmin_producto', array( $this, 'shortcode_producto' ) );
		add_action( 'wp_footer', array( $this, 'print_product' ), 20 );
	}

	/* ---------------------------------------------------------------------
	 * Utilidades
	 * ------------------------------------------------------------------ */

	/**
	 * Imprime un bloque <script type="application/ld+json"> con el array dado.
	 *
	 * - wp_json_encode() y no json_encode(): escapa pensando en el contexto HTML.
	 * - JSON_UNESCAPED_UNICODE: los acentos salen como "ñ", no como "ñ".
	 * - JSON_UNESCAPED_SLASHES: las URLs salen limpias, sin "https:\/\/".
	 * - JSON_PRETTY_PRINT: indentado, para poder leerlo en el código fuente.
	 * - El type es exactamente "application/ld+json". Cualquier otro y Google no lo lee.
	 *
	 * @param array $datos Estructura a serializar.
	 */
	private function print_jsonld( array $datos ) {
		if ( empty( $datos ) ) {
			return;
		}

		$json = wp_json_encode(
			$datos,
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
		);

		if ( false === $json ) {
			return; // si algo no es serializable, mejor ningún marcado que uno roto.
		}

		// Una cadena que contuviera "</script>" cerraría la etiqueta y rompería la
		// página: se neutraliza sin tocar el JSON (el escape \/ es válido en JSON).
		$json = str_replace( '</', '<\/', $json );

		echo '<script type="application/ld+json">' . "\n" . $json . "\n" . '</script>' . "\n";
	}

	/** Nombre del sitio, limpio. */
	private function nombre_sitio() {
		return wp_strip_all_tags( get_bloginfo( 'name' ) );
	}

	/**
	 * ¿Se ha pedido desde fuera que el plugin NO emita este tipo?
	 *
	 * Existe para que un TEMA que ya emite ese marcado (el caso de asdrubal, que
	 * conoce sus campos ACF y publica Car + Organization) pueda desactivarlo aquí.
	 * Dos bloques del mismo tipo en una página son dos entidades contradictorias,
	 * y Google no tiene forma de saber cuál es la buena.
	 *
	 *     add_filter( 'irmin_datos_estructurados_omitir', function ( $omitir, $tipo ) {
	 *         return 'organization' === $tipo ? true : $omitir;
	 *     }, 10, 2 );
	 *
	 * @param string $tipo Identificador del bloque: organization|article|faq|product|breadcrumb.
	 * @return bool
	 */
	private function omitido( $tipo ) {
		return (bool) apply_filters( 'irmin_datos_estructurados_omitir', false, $tipo );
	}

	/* ---------------------------------------------------------------------
	 * 0. Organization + WebSite (identidad del sitio, en todas las páginas)
	 * ------------------------------------------------------------------ */

	/**
	 * Un solo bloque con @graph: las dos entidades del sitio, enlazadas por @id.
	 * El @id evita repetir la organización dentro de cada artículo: se referencia.
	 */
	public function print_sitio() {
		if ( $this->omitido( 'organization' ) ) {
			return; // lo emite el tema.
		}

		$org = array(
			'@type' => 'Organization',
			'@id'   => $this->org_id,
			'name'  => $this->nombre_sitio(),
			'url'   => home_url( '/' ),
		);

		// El logo solo se declara si existe de verdad.
		$logo_id = (int) get_theme_mod( 'custom_logo' );
		if ( $logo_id ) {
			$logo_url = wp_get_attachment_image_url( $logo_id, 'full' );
			if ( $logo_url ) {
				$org['logo'] = array(
					'@type' => 'ImageObject',
					'url'   => $logo_url,
				);
			}
		}

		$web = array(
			'@type'     => 'WebSite',
			'@id'       => home_url( '/#website' ),
			'url'       => home_url( '/' ),
			'name'      => $this->nombre_sitio(),
			'publisher' => array( '@id' => $this->org_id ),
			'inLanguage' => get_bloginfo( 'language' ),
		);

		$descripcion = wp_strip_all_tags( get_bloginfo( 'description' ) );
		if ( $descripcion ) {
			$web['description'] = $descripcion;
		}

		$this->print_jsonld(
			array(
				'@context' => 'https://schema.org',
				'@graph'   => array( $org, $web ),
			)
		);
	}

	/* ---------------------------------------------------------------------
	 * 1. BlogPosting — entradas individuales
	 * ------------------------------------------------------------------ */

	/**
	 * Marcado de artículo. Solo en entradas ("post"), nunca en toda la web.
	 *
	 * headline se recorta a 110 caracteres porque por encima Google puede
	 * invalidar el resultado enriquecido; las fechas salen en ISO 8601 CON zona
	 * horaria gracias a get_the_date('c'); y author es un objeto Person con url,
	 * no una cadena suelta — que es lo que conecta el artículo con la entidad autor.
	 */
	public function print_article() {
		if ( ! is_singular( 'post' ) || $this->omitido( 'article' ) ) {
			return;
		}

		$post = get_queried_object();
		if ( ! $post instanceof WP_Post ) {
			return;
		}

		$autor_id = (int) $post->post_author;

		$datos = array(
			'@context'         => 'https://schema.org',
			'@type'            => 'BlogPosting',
			'@id'              => get_permalink( $post ) . '#article',
			'mainEntityOfPage' => array(
				'@type' => 'WebPage',
				'@id'   => get_permalink( $post ),
			),
			'headline'         => mb_substr( wp_strip_all_tags( get_the_title( $post ) ), 0, 110 ),
			'datePublished'    => get_the_date( 'c', $post ),
			'dateModified'     => get_the_modified_date( 'c', $post ),
			'author'           => array(
				'@type' => 'Person',
				'name'  => get_the_author_meta( 'display_name', $autor_id ),
				'url'   => get_author_posts_url( $autor_id ),
			),
			'publisher'        => array( '@id' => $this->org_id ),
			'inLanguage'       => get_bloginfo( 'language' ),
		);

		// image: varias resoluciones si las hay; la propiedad solo aparece si existe.
		if ( has_post_thumbnail( $post ) ) {
			$imagenes = array();
			foreach ( array( 'full', 'large', 'medium_large' ) as $tamano ) {
				$src = get_the_post_thumbnail_url( $post, $tamano );
				if ( $src ) {
					$imagenes[] = $src;
				}
			}
			if ( $imagenes ) {
				// array_values() tras unique: si no, el JSON saldría como objeto {"0":..},
				// no como array — un fallo de sintaxis silencioso y clásico.
				$datos['image'] = array_values( array_unique( $imagenes ) );
			}
		}

		$extracto = wp_strip_all_tags( get_the_excerpt( $post ) );
		if ( $extracto ) {
			$datos['description'] = $extracto;
		}

		// Las palabras clave salen de las etiquetas reales del post.
		$etiquetas = get_the_tags( $post );
		if ( $etiquetas && ! is_wp_error( $etiquetas ) ) {
			$datos['keywords'] = wp_list_pluck( $etiquetas, 'name' );
		}

		// La sección sale de la categoría principal.
		$categorias = get_the_category( $post->ID );
		if ( $categorias ) {
			$datos['articleSection'] = $categorias[0]->name;
		}

		$this->print_jsonld( $datos );
	}

	/* ---------------------------------------------------------------------
	 * 2. FAQPage — extraído del contenido VISIBLE
	 * ------------------------------------------------------------------ */

	/**
	 * Extrae pares pregunta/respuesta de los encabezados del contenido.
	 *
	 * Es pregunta si el encabezado (h2/h3/h4) termina en "?" y va seguido de texto.
	 * Sacarlo del propio contenido garantiza que TODO lo marcado está visible en la
	 * página: el requisito duro de Google para FAQPage. Un campo oculto en la base
	 * de datos daría el mismo JSON y sería motivo de acción manual.
	 *
	 * @param WP_Post $post El contenido.
	 * @return array<int, array{pregunta:string, respuesta:string}>
	 */
	private function extraer_faqs( WP_Post $post ) {
		$contenido = $post->post_content;
		if ( ! $contenido ) {
			return array();
		}

		// Fuera los comentarios de bloque de Gutenberg (<!-- wp:heading -->).
		$contenido = preg_replace( '/<!--.*?-->/s', '', $contenido );

		$patron = '/<h([2-4])[^>]*>(.*?)<\/h\1>(.*?)(?=<h[2-4][^>]*>|$)/is';
		if ( ! preg_match_all( $patron, $contenido, $bloques, PREG_SET_ORDER ) ) {
			return array();
		}

		$faqs = array();
		foreach ( $bloques as $bloque ) {
			$pregunta  = trim( wp_strip_all_tags( $bloque[2] ) );
			$respuesta = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $bloque[3] ) ) );

			// Sin interrogación de cierre no es una pregunta; sin texto debajo no hay respuesta.
			if ( '' === $respuesta || ! preg_match( '/\?\s*$/u', $pregunta ) ) {
				continue;
			}

			$faqs[] = array(
				'pregunta'  => $pregunta,
				'respuesta' => $respuesta,
			);
		}

		return $faqs;
	}

	/**
	 * Emite FAQPage si el contenido tiene al menos dos preguntas reales.
	 *
	 * Nota de actualidad: el resultado enriquecido de FAQ ya NO existe. Google lo
	 * restringió a gobierno y sanidad en agosto de 2023 y lo retiró por completo
	 * el 7 de mayo de 2026 (documentación borrada en junio de 2026). El marcado
	 * FAQPage sigue siendo schema.org válido y lo leen otros buscadores y los
	 * LLMs, así que se mantiene — pero no genera ninguna apariencia en Google.
	 */
	public function print_faq() {
		if ( ! is_singular() || $this->omitido( 'faq' ) ) {
			return;
		}

		$post = get_queried_object();
		if ( ! $post instanceof WP_Post ) {
			return;
		}

		$faqs = $this->extraer_faqs( $post );
		if ( count( $faqs ) < 2 ) {
			return; // una sola pregunta no es una FAQ.
		}

		$preguntas = array();
		foreach ( $faqs as $faq ) {
			$preguntas[] = array(
				'@type'          => 'Question',
				'name'           => $faq['pregunta'],
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => $faq['respuesta'],
				),
			);
		}

		$this->print_jsonld(
			array(
				'@context'   => 'https://schema.org',
				'@type'      => 'FAQPage',
				'@id'        => get_permalink( $post ) . '#faq',
				'inLanguage' => get_bloginfo( 'language' ),
				'mainEntity' => $preguntas,
			)
		);
	}

	/* ---------------------------------------------------------------------
	 * 3. Product — shortcode que pinta la ficha Y alimenta el marcado
	 * ------------------------------------------------------------------ */

	/**
	 * [irmin_producto nombre="" precio="29.95" moneda="EUR" disponibilidad="InStock"
	 *                 sku="" marca="" imagen="" descripcion="" condicion="NewCondition"]
	 *
	 * Pinta la ficha visible y guarda los MISMOS datos para el JSON-LD del footer.
	 * Una sola fuente -> el precio del marcado nunca puede diferir del de la página.
	 *
	 * @param array $atts Atributos del shortcode.
	 * @return string HTML de la ficha.
	 */
	public function shortcode_producto( $atts ) {
		$a = shortcode_atts(
			array(
				'nombre'         => '',
				'descripcion'    => '',
				'precio'         => '',
				'moneda'         => 'EUR',
				'disponibilidad' => 'InStock',
				'condicion'      => 'NewCondition',
				'sku'            => '',
				'marca'          => '',
				'imagen'         => '',
			),
			$atts,
			'irmin_producto'
		);

		if ( '' === $a['nombre'] ) {
			return ''; // sin nombre no hay producto que valga.
		}

		// El precio va como número limpio: ni símbolo de moneda ni coma decimal.
		$precio = str_replace( array( '€', '$', ' ' ), '', $a['precio'] );
		$precio = str_replace( ',', '.', $precio );

		$producto = array(
			'@type' => 'Product',
			'@id'   => get_permalink() . '#producto-' . ( count( $this->productos ) + 1 ),
			'name'  => wp_strip_all_tags( $a['nombre'] ),
		);

		if ( $a['descripcion'] ) {
			$producto['description'] = wp_strip_all_tags( $a['descripcion'] );
		}
		if ( $a['imagen'] ) {
			$producto['image'] = array( esc_url_raw( $a['imagen'] ) );
		}
		if ( $a['sku'] ) {
			$producto['sku'] = $a['sku'];
		}
		if ( $a['marca'] ) {
			$producto['brand'] = array(
				'@type' => 'Brand',
				'name'  => wp_strip_all_tags( $a['marca'] ),
			);
		}
		if ( is_numeric( $precio ) ) {
			$producto['offers'] = array(
				'@type'         => 'Offer',
				'url'           => get_permalink(),
				'price'         => number_format( (float) $precio, 2, '.', '' ),
				'priceCurrency' => strtoupper( $a['moneda'] ), // ISO 4217: EUR, USD...
				// availability e itemCondition llevan la URL COMPLETA de schema.org.
				'availability'  => 'https://schema.org/' . sanitize_text_field( $a['disponibilidad'] ),
				'itemCondition' => 'https://schema.org/' . sanitize_text_field( $a['condicion'] ),
				'seller'        => array( '@id' => $this->org_id ),
			);
		}

		$this->productos[] = $producto;

		// Ficha visible: los mismos valores que acaban de entrar en el marcado.
		$disponible = 'InStock' === $a['disponibilidad'];

		ob_start();
		?>
		<div class="irmin-producto" style="border:1px solid #ddd;border-radius:8px;padding:1rem;max-width:32rem;">
			<?php if ( $a['imagen'] ) : ?>
				<img src="<?php echo esc_url( $a['imagen'] ); ?>" alt="<?php echo esc_attr( $a['nombre'] ); ?>" style="max-width:100%;height:auto;" />
			<?php endif; ?>

			<h3 style="margin:.5rem 0;"><?php echo esc_html( $a['nombre'] ); ?></h3>

			<?php if ( $a['marca'] ) : ?>
				<p style="margin:0;color:#666;"><?php echo esc_html( $a['marca'] ); ?></p>
			<?php endif; ?>

			<?php if ( $a['descripcion'] ) : ?>
				<p><?php echo esc_html( $a['descripcion'] ); ?></p>
			<?php endif; ?>

			<?php if ( is_numeric( $precio ) ) : ?>
				<p style="font-size:1.25rem;font-weight:700;margin:.5rem 0;">
					<?php echo esc_html( number_format( (float) $precio, 2, ',', '.' ) . ' ' . strtoupper( $a['moneda'] ) ); ?>
				</p>
				<p style="margin:0;color:<?php echo $disponible ? '#0a7' : '#a00'; ?>;">
					<?php echo $disponible ? esc_html__( 'En stock', 'irmin-datos-estructurados' ) : esc_html__( 'Sin stock', 'irmin-datos-estructurados' ); ?>
				</p>
			<?php endif; ?>

			<?php if ( $a['sku'] ) : ?>
				<p style="margin:.5rem 0 0;color:#666;font-size:.875rem;">SKU: <?php echo esc_html( $a['sku'] ); ?></p>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/** Emite en el footer los productos declarados en esta página. */
	public function print_product() {
		if ( empty( $this->productos ) || $this->omitido( 'product' ) ) {
			return;
		}

		// Un producto -> objeto suelto. Varios -> @graph. Las dos formas son válidas.
		if ( 1 === count( $this->productos ) ) {
			$datos             = $this->productos[0];
			$datos['@context'] = 'https://schema.org';
			$this->print_jsonld( $datos );
			return;
		}

		$this->print_jsonld(
			array(
				'@context' => 'https://schema.org',
				'@graph'   => $this->productos,
			)
		);
	}

	/* ---------------------------------------------------------------------
	 * 4. BreadcrumbList — migas de pan
	 * ------------------------------------------------------------------ */

	/**
	 * Migas de pan de la URL actual: Inicio > (categoría) > título.
	 * Cada ListItem lleva su "position" empezando en 1, sin saltos.
	 */
	public function print_breadcrumbs() {
		if ( ! is_singular() || $this->omitido( 'breadcrumb' ) ) {
			return;
		}

		$post = get_queried_object();
		if ( ! $post instanceof WP_Post ) {
			return;
		}

		$migas = array(
			array(
				'nombre' => __( 'Inicio', 'irmin-datos-estructurados' ),
				'url'    => home_url( '/' ),
			),
		);

		if ( 'post' === $post->post_type ) {
			$categorias = get_the_category( $post->ID );
			if ( $categorias ) {
				$migas[] = array(
					'nombre' => $categorias[0]->name,
					'url'    => get_category_link( $categorias[0]->term_id ),
				);
			}
		} elseif ( $post->post_parent ) {
			$migas[] = array(
				'nombre' => wp_strip_all_tags( get_the_title( $post->post_parent ) ),
				'url'    => get_permalink( $post->post_parent ),
			);
		}

		$migas[] = array(
			'nombre' => wp_strip_all_tags( get_the_title( $post ) ),
			'url'    => get_permalink( $post ),
		);

		$items = array();
		foreach ( $migas as $indice => $miga ) {
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $indice + 1, // número, sin comillas.
				'name'     => $miga['nombre'],
				'item'     => $miga['url'],
			);
		}

		$this->print_jsonld(
			array(
				'@context'        => 'https://schema.org',
				'@type'           => 'BreadcrumbList',
				'itemListElement' => $items,
			)
		);
	}
}

new Irmin_Datos_Estructurados();
