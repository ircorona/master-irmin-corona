<?php 
//include_once 'header.php';
get_header();
?>

<div class="container">
    <h1><?php single_cat_title();?></h1>
    <section id="content">
        <?php echo category_description();?>
    </section>
    <section>
     <?php
        $args = array(
            'cat' => get_query_var('cat'),
            'posts_per_page' => -1,
            'post_type' => 'post',
            'orderby' => 'date',
            'order' => 'DESC',
        );
        $the_query = new WP_Query($args);

        if ($the_query->have_posts()) {
            while ($the_query->have_posts()) {
                $the_query->the_post();
                include get_template_directory() . '/templates/display-posts/card-posts.php';
            }
        } else {
            echo "No hay posts";
        }
        wp_reset_postdata();
    ?>
    </section>
</div>

<?php 
// include_once 'footer.php';
get_footer();
?>



$dominio_actual = $_SERVER['HTTP_HOST'];

if ($dominio_actual == 'danileitner.ch.test') {
define( 'DB_NAME', 'mitesubi_dani' );
/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );
define( 'DB_HOST', 'localhost' );
} 
else{