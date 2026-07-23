<?php
/*
Plugin Name: Irmin Sitemap Generator
Plugin URI: https://climbthesearches.com
Description: Genera automáticamente un sitemap XML (irmin.xml) con páginas, entradas y custom post types publicados. Incluye una plantilla generadora y regeneración automática al publicar/actualizar. Ejercicio clase 12 — sitemap automático.
Author: Irmin Corona
Author URI: https://climbthesearches.com
Version: 1.0.0
License: GPLv2 or later
Text Domain: irmin-sitemap-generator
*/

// Seguridad: sin acceso directo al fichero.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Genera un sitemap XML automático para TODO el contenido público.
 *
 * Dos vías de automatización:
 *   1. Plantilla "Generador de Sitemap (Irmin)": al visitar la página (admin),
 *      regenera irmin.xml y muestra el XML resultante.
 *   2. Hook transition_post_status: regenera solo (sin visitar nada) cada vez que
 *      se publica / actualiza / despublica una página, entrada o CPT.
 */
class Irmin_Sitemap_Generator {

	/** Clave virtual de la plantilla generadora. */
	const TEMPLATE_SLUG = 'irmin-sitemap-generador';

	/** Nombre del fichero de salida (el NOMBRE DEL AUTOR, no de ninguna herramienta). */
	const OUTPUT_FILE = 'irmin.xml';

	public function __construct() {
		// 1) Plantilla generadora (página que regenera al visitarse).
		add_filter( 'theme_page_templates', array( $this, 'register_template' ) );
		add_filter( 'template_include', array( $this, 'load_template' ) );

		// 2) Backend automático: regenerar al cambiar el estado de un contenido.
		add_action( 'transition_post_status', array( $this, 'on_transition' ), 10, 3 );

		// 3) Backend manual: página en Herramientas con botón "Generar ahora".
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_post_irmin_generate_sitemap', array( $this, 'handle_admin_generate' ) );

		// 4) Endpoint para cron (clase 15), protegido por IP: /?irmin_generate=1
		add_action( 'init', array( $this, 'maybe_generate_via_url' ) );
	}

	/**
	 * Tipos de contenido públicos: páginas + entradas + custom post types.
	 * El exercise pide incluir PÁGINAS, no solo posts -> aquí entran todos.
	 *
	 * @return string[]
	 */
	public function public_post_types() {
		$types = get_post_types( array( 'public' => true ), 'names' );
		unset( $types['attachment'] ); // los adjuntos no van al sitemap.
		// Filtro para poder ajustar la lista desde fuera sin tocar el plugin.
		return apply_filters( 'irmin_sitemap_post_types', array_values( $types ) );
	}

	/**
	 * Añade etiquetas <image:image> al <url> con las imágenes de ese contenido.
	 * Combina la imagen destacada + las <img> incrustadas en el contenido.
	 * (Solo sobrevive <image:loc>; caption/title/geo/license están deprecadas.)
	 *
	 * Variables con nombre propio para NO pisar el resto del generador
	 * ("el nombre de las variables puede afectar").
	 *
	 * @param DOMDocument $dom El documento.
	 * @param DOMElement  $url El nodo <url>.
	 * @param int         $id  ID del contenido.
	 */
	private function add_image_nodes( DOMDocument $dom, DOMElement $url, $id ) {
		$image_urls = array();

		// 1) Imagen destacada (featured image).
		$thumb_id = get_post_thumbnail_id( $id );
		if ( $thumb_id ) {
			$thumb_url = wp_get_attachment_image_url( $thumb_id, 'full' );
			if ( $thumb_url ) {
				$image_urls[] = $thumb_url;
			}
		}

		// 2) Imágenes incrustadas en el contenido (<img src="...">).
		$post_content = get_post_field( 'post_content', $id );
		if ( $post_content && preg_match_all( '/<img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>/i', $post_content, $img_matches ) ) {
			foreach ( $img_matches[1] as $img_src ) {
				$image_urls[] = $img_src;
			}
		}

		// Limpiar, deduplicar y aplicar el límite de Google (1000 imágenes por URL).
		$image_urls = array_slice( array_unique( array_filter( $image_urls ) ), 0, 1000 );

		foreach ( $image_urls as $image_src ) {
			$image_node = $dom->createElement( 'image:image' );
			$image_loc  = $dom->createElement( 'image:loc' );
			$image_loc->appendChild( $dom->createTextNode( $image_src ) );
			$image_node->appendChild( $image_loc );
			$url->appendChild( $image_node );
		}
	}

	/**
	 * Tipos considerados "noticia" para las etiquetas <news:news>. Por defecto: post.
	 *
	 * @return string[]
	 */
	public function news_post_types() {
		return apply_filters( 'irmin_sitemap_news_post_types', array( 'post' ) );
	}

	/**
	 * Añade el bloque <news:news> a una entrada SI es de tipo noticia y se publicó
	 * en los últimos 2 días (recomendación de Google News: solo URLs recientes;
	 * pasados 2 días se quita el <news:news>, aunque la URL siga indexada 30 días).
	 *
	 * @param DOMDocument $dom El documento.
	 * @param DOMElement  $url El nodo <url> al que colgar el bloque.
	 * @param int         $id  ID del contenido.
	 */
	private function add_news_node( DOMDocument $dom, DOMElement $url, $id ) {
		if ( ! in_array( get_post_type( $id ), $this->news_post_types(), true ) ) {
			return;
		}

		// Condicional de los 2 días (sobre la fecha de publicación).
		$published_ts = (int) get_post_time( 'U', true, $id );
		$days_old     = floor( ( time() - $published_ts ) / DAY_IN_SECONDS );
		if ( $days_old > 2 ) {
			return;
		}

		$news = $dom->createElement( 'news:news' );

		// <news:publication>: nombre del MEDIO (el sitio) + idioma.
		$publication = $dom->createElement( 'news:publication' );

		$name = $dom->createElement( 'news:name' );
		$name->appendChild( $dom->createTextNode( get_bloginfo( 'name' ) ) ); // publicación = el sitio, NO el título.
		$publication->appendChild( $name );

		$language = $dom->createElement( 'news:language' );
		$language->appendChild( $dom->createTextNode( substr( get_locale(), 0, 2 ) ) ); // 'es' desde el locale.
		$publication->appendChild( $language );

		$news->appendChild( $publication );

		// <news:publication_date>: W3C con hora + zona (la hora importa en noticias).
		$pub_date = $dom->createElement( 'news:publication_date' );
		$pub_date->appendChild( $dom->createTextNode( get_post_time( 'c', true, $id ) ) );
		$news->appendChild( $pub_date );

		// <news:title>: título del ARTÍCULO.
		$title = $dom->createElement( 'news:title' );
		$title->appendChild( $dom->createTextNode( get_the_title( $id ) ) );
		$news->appendChild( $title );

		$url->appendChild( $news );
	}

	/**
	 * Construye un <urlset> con los tipos indicados (reutilizable para el sitemap
	 * único y para los segmentados del índice). Eficiente: una WP_Query, solo IDs.
	 *
	 * @param string[] $post_types Tipos de contenido a incluir.
	 * @return DOMDocument
	 */
	private function build_urlset( array $post_types ) {
		$dom                     = new DOMDocument( '1.0', 'UTF-8' );
		$dom->formatOutput       = true;
		$dom->preserveWhiteSpace = false;

		$urlset = $dom->createElement( 'urlset' );
		$urlset->setAttribute( 'xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9' );
		$urlset->setAttribute( 'xmlns:image', 'http://www.google.com/schemas/sitemap-image/1.1' );
		$urlset->setAttribute( 'xmlns:news', 'http://www.google.com/schemas/sitemap-news/0.9' );
		$dom->appendChild( $urlset );

		$query = new WP_Query(
			array(
				'post_type'      => $post_types,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',     // eficiencia: solo IDs
				'no_found_rows'  => true,      // eficiencia: sin contar para paginación
				'orderby'        => 'modified',
				'order'          => 'DESC',
			)
		);

		foreach ( $query->posts as $id ) {
			if ( $this->is_noindex( $id ) ) {
				continue; // no metemos en el sitemap lo que está marcado noindex.
			}

			$url = $dom->createElement( 'url' );

			$loc = $dom->createElement( 'loc' );
			$loc->appendChild( $dom->createTextNode( get_permalink( $id ) ) ); // createTextNode escapa &, etc.
			$url->appendChild( $loc );

			$lastmod = $dom->createElement( 'lastmod' );
			$lastmod->appendChild( $dom->createTextNode( get_post_modified_time( 'Y-m-d', true, $id ) ) );
			$url->appendChild( $lastmod );

			// Etiquetas de imágenes (clase 14): destacada + imágenes del contenido.
			$this->add_image_nodes( $dom, $url, $id );

			// Etiquetas de noticias (solo posts recientes). Clase 13.
			$this->add_news_node( $dom, $url, $id );

			$urlset->appendChild( $url );
		}

		return $dom;
	}

	/**
	 * Genera el sitemap "todo en uno" (irmin.xml) con todo el contenido público.
	 *
	 * @return array{count:int, path:string, xml:string}
	 */
	public function generate() {
		$dom  = $this->build_urlset( $this->public_post_types() );
		$xml  = $dom->saveXML();
		$path = trailingslashit( ABSPATH ) . self::OUTPUT_FILE;
		file_put_contents( $path, $xml ); // phpcs:ignore -- en local basta; en prod usar WP_Filesystem.

		return array(
			'count' => $dom->getElementsByTagName( 'url' )->length,
			'path'  => $path,
			'xml'   => $xml,
		);
	}

	/**
	 * Clase 15 — sitemap INDEX: genera un sitemap por cada tipo de contenido
	 * (irmin-{tipo}.xml) y un índice XML (irmin-index.xml) que los agrupa.
	 * Segmentar así permite medir la indexación por tipo en Search Console.
	 *
	 * @return array{count:int, files:string[]}
	 */
	public function generate_index() {
		$index               = new DOMDocument( '1.0', 'UTF-8' );
		$index->formatOutput = true;

		$sitemapindex = $index->createElement( 'sitemapindex' );
		$sitemapindex->setAttribute( 'xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9' );
		$index->appendChild( $sitemapindex );

		$files = array();
		foreach ( $this->public_post_types() as $type ) {
			$dom = $this->build_urlset( array( $type ) );
			if ( 0 === $dom->getElementsByTagName( 'url' )->length ) {
				continue; // no creamos sitemaps vacíos.
			}

			$file = 'irmin-' . sanitize_key( $type ) . '.xml';
			file_put_contents( trailingslashit( ABSPATH ) . $file, $dom->saveXML() ); // phpcs:ignore
			$files[] = $file;

			$sitemap = $index->createElement( 'sitemap' );

			$loc = $index->createElement( 'loc' );
			$loc->appendChild( $index->createTextNode( home_url( '/' . $file ) ) );
			$sitemap->appendChild( $loc );

			$lastmod = $index->createElement( 'lastmod' );
			$lastmod->appendChild( $index->createTextNode( current_time( 'Y-m-d' ) ) );
			$sitemap->appendChild( $lastmod );

			$sitemapindex->appendChild( $sitemap );
		}

		file_put_contents( trailingslashit( ABSPATH ) . 'irmin-index.xml', $index->saveXML() ); // phpcs:ignore

		return array(
			'count' => count( $files ),
			'files' => $files,
		);
	}

	/** Regenera todo: el sitemap único (irmin.xml) + los segmentados + el índice. */
	public function regenerate_all() {
		$this->generate();
		$this->generate_index();
	}

	/**
	 * Clase 15 — sistema de seguridad (como el generador de Carlos): solo se
	 * permite disparar la generación por URL desde el PROPIO servidor / localhost.
	 * Cualquier IP externa recibe 403. Pensado para cron (curl local) sin exponer
	 * el endpoint a Internet.
	 *
	 * @return bool
	 */
	private function is_local_request() {
		$server_name = isset( $_SERVER['SERVER_NAME'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) ) : gethostname();
		$server_ip   = gethostbyname( $server_name );
		$remote_ip   = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		return in_array( $remote_ip, array( $server_ip, '127.0.0.1', '::1' ), true );
	}

	/**
	 * Endpoint para cron: /?irmin_generate=1 regenera TODOS los sitemaps,
	 * protegido por IP (403 si no es local). Ej. cron: curl http://127.0.0.1/?irmin_generate=1
	 */
	public function maybe_generate_via_url() {
		if ( ! isset( $_GET['irmin_generate'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		if ( ! $this->is_local_request() ) {
			http_response_code( 403 );
			exit( '403 Forbidden' );
		}
		$this->regenerate_all();
		http_response_code( 200 );
		exit( 'OK: sitemaps regenerados' );
	}

	/**
	 * Detección genérica de noindex (Yoast / Rank Math), sin depender de ACF.
	 *
	 * @param int $id ID del contenido.
	 * @return bool
	 */
	private function is_noindex( $id ) {
		if ( '1' === get_post_meta( $id, '_yoast_wpseo_meta-robots-noindex', true ) ) {
			return true;
		}
		$rank = get_post_meta( $id, 'rank_math_robots', true );
		if ( is_array( $rank ) && in_array( 'noindex', $rank, true ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Regenera el sitemap al publicar / actualizar / despublicar contenido.
	 *
	 * @param string  $new_status Nuevo estado.
	 * @param string  $old_status Estado anterior.
	 * @param WP_Post $post       El post.
	 */
	public function on_transition( $new_status, $old_status, $post ) {
		if ( wp_is_post_revision( $post ) || wp_is_post_autosave( $post ) ) {
			return;
		}
		// Solo si entra o sale de "publish" (publicar o despublicar).
		if ( 'publish' !== $new_status && 'publish' !== $old_status ) {
			return;
		}
		if ( in_array( $post->post_type, $this->public_post_types(), true ) ) {
			$this->regenerate_all();
		}
	}

	/** Añade la plantilla generadora al desplegable del editor. */
	public function register_template( $templates ) {
		$templates[ self::TEMPLATE_SLUG ] = __( 'Generador de Sitemap (Irmin)', 'irmin-sitemap-generator' );
		return $templates;
	}

	/** Carga el fichero de la plantilla cuando la página la tiene asignada. */
	public function load_template( $template ) {
		if ( is_page() && self::TEMPLATE_SLUG === get_page_template_slug( get_queried_object_id() ) ) {
			$custom = plugin_dir_path( __FILE__ ) . 'templates/generador.php';
			if ( file_exists( $custom ) ) {
				return $custom;
			}
		}
		return $template;
	}

	/** Añade "Sitemap Irmin" bajo el menú Herramientas. */
	public function admin_menu() {
		add_management_page(
			__( 'Sitemap Irmin', 'irmin-sitemap-generator' ),
			__( 'Sitemap Irmin', 'irmin-sitemap-generator' ),
			'manage_options',
			'irmin-sitemap',
			array( $this, 'admin_page' )
		);
	}

	/** Pantalla del backend: estado actual + botón de generación manual. */
	public function admin_page() {
		$sitemap_url = home_url( '/' . self::OUTPUT_FILE );
		$index_url   = home_url( '/irmin-index.xml' );
		$exists      = file_exists( trailingslashit( ABSPATH ) . self::OUTPUT_FILE );
		$generated   = isset( $_GET['generated'] ) ? (int) $_GET['generated'] : -1; // phpcs:ignore WordPress.Security.NonceVerification
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Sitemap Irmin', 'irmin-sitemap-generator' ); ?></h1>

			<?php if ( $generated >= 0 ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php printf( esc_html__( 'Sitemap generado con %d URLs.', 'irmin-sitemap-generator' ), $generated ); ?></p>
				</div>
			<?php endif; ?>

			<p>
				<?php if ( $exists ) : ?>
					<?php esc_html_e( 'Ficheros:', 'irmin-sitemap-generator' ); ?>
					<a href="<?php echo esc_url( $sitemap_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $sitemap_url ); ?></a>
					&nbsp;·&nbsp;
					<a href="<?php echo esc_url( $index_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $index_url ); ?></a>
				<?php else : ?>
					<em><?php esc_html_e( 'Todavía no se ha generado el sitemap. Pulsa el botón.', 'irmin-sitemap-generator' ); ?></em>
				<?php endif; ?>
			</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="irmin_generate_sitemap" />
				<?php wp_nonce_field( 'irmin_generate_sitemap' ); ?>
				<?php submit_button( __( 'Generar sitemap ahora', 'irmin-sitemap-generator' ) ); ?>
			</form>

			<p class="description">
				<?php esc_html_e( 'Cron (solo local, 403 desde fuera):', 'irmin-sitemap-generator' ); ?>
				<code>curl <?php echo esc_html( home_url( '/?irmin_generate=1' ) ); ?></code>
			</p>
		</div>
		<?php
	}

	/** Handler del botón: valida permisos + nonce, genera y vuelve. */
	public function handle_admin_generate() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sin permisos.', 'irmin-sitemap-generator' ) );
		}
		check_admin_referer( 'irmin_generate_sitemap' );
		$result = $this->generate();
		$this->generate_index(); // clase 15: regenerar también los segmentados + el índice.
		wp_safe_redirect( add_query_arg( 'generated', (int) $result['count'], admin_url( 'tools.php?page=irmin-sitemap' ) ) );
		exit;
	}
}

$GLOBALS['irmin_sitemap_generator'] = new Irmin_Sitemap_Generator();

// Genera los sitemaps al activar el plugin (así no hay que esperar a publicar nada).
register_activation_hook(
	__FILE__,
	function () {
		if ( isset( $GLOBALS['irmin_sitemap_generator'] ) ) {
			$GLOBALS['irmin_sitemap_generator']->regenerate_all();
		}
	}
);
