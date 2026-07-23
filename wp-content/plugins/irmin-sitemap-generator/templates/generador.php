<?php
/**
 * Plantilla: Generador de Sitemap (Irmin).
 *
 * Al visitarla un administrador, regenera irmin.xml y muestra el XML.
 * Para visitantes normales solo enseña el enlace de comprobación (no regenera:
 * de eso ya se encarga el hook transition_post_status en segundo plano).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

/** @var Irmin_Sitemap_Generator $generator */
$generator    = $GLOBALS['irmin_sitemap_generator'];
$sitemap_url  = home_url( '/' . Irmin_Sitemap_Generator::OUTPUT_FILE );
$can_generate = current_user_can( 'manage_options' );
$result       = $can_generate ? $generator->generate() : null;
?>

<main class="irmin-generador" style="max-width:900px;margin:0 auto;padding:24px;">

	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<h1><?php the_title(); ?></h1>
	<?php endwhile; ?>

	<?php if ( $can_generate ) : ?>
		<p>
			Sitemap <strong><?php echo esc_html( Irmin_Sitemap_Generator::OUTPUT_FILE ); ?></strong>
			regenerado con <strong><?php echo (int) $result['count']; ?></strong> URLs
			(páginas, entradas y custom post types publicados).
		</p>
	<?php else : ?>
		<p>El sitemap se regenera automáticamente al publicar contenido.</p>
	<?php endif; ?>

	<p>
		<a class="exitbutton" href="<?php echo esc_url( $sitemap_url ); ?>" target="_blank" rel="noopener">
			Comprobar sitemap &rarr;
		</a>
	</p>

	<?php if ( $can_generate ) : ?>
		<pre class="codigo-post" style="background:#0c1021;color:#fff;padding:40px 16px 16px;overflow:auto;max-width:100%;"><?php
			echo esc_html( $result['xml'] );
		?></pre>
	<?php endif; ?>

</main>

<?php
get_footer();
