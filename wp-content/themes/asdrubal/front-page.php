<?php
//include_once 'header.php';
get_header();
$templates = get_template_directory() . '/templates/';
?>

<div class="container">
    <h1><?php the_title();?></h1>
    <section id="content">
        <?php the_content();?>
    </section>
    <?php
    include $templates . 'trespost.php';
    include $templates . 'trespost.php';
    ?>
</div>

<?php 
// include_once 'footer.php';
get_footer();
?>