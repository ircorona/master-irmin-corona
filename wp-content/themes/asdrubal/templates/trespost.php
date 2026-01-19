<section id="displaytresposts" class="flexcenter">
    <?php
        $args = array(
            'posts_per_page' => 3,
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
