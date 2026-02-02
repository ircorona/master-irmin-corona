<?php 
//include_once 'header.php';
get_header();
?>

<div class="container">
    <h1><?php the_title();?></h1>
    <section id="content">
        <?php the_content();?>
    </section>

    <?php if (in_category('cars')) : ?>
    <table>
        <tr>
            <th colspan="2">Ficha Tecnica</th>
        </tr>
        <tr>
            <th>Modelo</th>
            <td><?php the_title(); ?></td>
        </tr>
        <tr>
            <th>Horse Power</th>
            <td><?php the_field('hp'); ?></td>
        </tr>
        <tr>
            <th>Price</th>
            <td><?php the_field('price'); ?></td>
        </tr>
        <tr>
            <th>Fuel</th>
            <td><?php the_field('fuel'); ?></td>
        </tr>
        <tr>
            <th>Brand</th>
            <td><?php the_field('brand'); ?></td>
        </tr>
    </table>
    <?php endif; ?>
</div>

<?php 
// include_once 'footer.php';
get_footer();
?>