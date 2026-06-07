<?php
/**
 * Plantilla: Sitemap (Irmin)
 *
 * Genera un mapa del sitio en HTML con las páginas y entradas publicadas.
 * La carga el plugin Irmin Sitemap Template vía el filtro template_include
 * (NO es una plantilla del tema; vive dentro del plugin).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main class="irmin-sitemap" style="max-width:900px;margin:0 auto;padding:24px;">

	<?php
	// Contenido propio de la página (el título y el texto que escriba el editor).
	while ( have_posts() ) :
		the_post();
		?>
		<h1><?php the_title(); ?></h1>
		<div class="irmin-sitemap__intro"><?php the_content(); ?></div>
	<?php endwhile; ?>

	<?php
	// --- Páginas publicadas ---
	$irmin_pages = get_pages(
		array(
			'sort_column' => 'menu_order,post_title',
			'post_status' => 'publish',
		)
	);
	if ( $irmin_pages ) :
		?>
		<h2>Páginas</h2>
		<ul>
			<?php foreach ( $irmin_pages as $irmin_page ) : ?>
				<li>
					<a href="<?php echo esc_url( get_permalink( $irmin_page->ID ) ); ?>">
						<?php echo esc_html( $irmin_page->post_title ); ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<?php
	// --- Entradas publicadas ---
	$irmin_posts = get_posts(
		array(
			'numberposts' => -1,
			'post_status' => 'publish',
			'orderby'     => 'date',
			'order'       => 'DESC',
		)
	);
	if ( $irmin_posts ) :
		?>
		<h2>Entradas</h2>
		<ul>
			<?php foreach ( $irmin_posts as $irmin_post ) : ?>
				<li>
					<a href="<?php echo esc_url( get_permalink( $irmin_post->ID ) ); ?>">
						<?php echo esc_html( get_the_title( $irmin_post->ID ) ); ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

</main>

<?php
get_footer();
