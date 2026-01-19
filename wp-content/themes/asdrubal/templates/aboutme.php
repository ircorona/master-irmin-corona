<?php
/**
 * Template Name: about me
 */
get_header();
?>

<div class=""> 
    <h1><?php the_title();?></h1>
    <section id="content">
        <?php the_content();?>
    </section>
    <?php
    include get_template_directory() . '/templates/trespost.php'; 
    ?>
</div>

<?php 
// include_once __DIR__ . ('/../footer.php');
get_footer();
?>