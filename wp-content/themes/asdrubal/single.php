<?php 
//include_once 'header.php';
get_header();
?>

<div class="container">
    <h1><?php the_title();?></h1>
    <section id="content">
        <?php the_content();?>
    </section>
</div>

<?php 
// include_once 'footer.php';
get_footer();
?>