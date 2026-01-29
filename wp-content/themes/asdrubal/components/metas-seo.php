<?php
$term = get_queried_object();
?>

<!-- metas-seo.php -->

<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta charset="UTF-8">

<!-- Custom Meta Tags -->
<?php the_field('custom_meta', $term); ?>

<title><?php the_field('title', $term); ?></title>

<meta name="description" content="<?php the_field('metadescription', $term); ?>" />
<link rel="canonical" href="<?php the_field('canonical', $term); ?>" />

<?php if (get_field('og_image', $term)) : ?>
    <meta property="og:image" content="<?php the_field('og_image', $term); ?>" />
    <meta property="og:image:secure_url" content="<?php the_field('og_image', $term); ?>" />
    <meta property="twitter:image" content="<?php the_field('og_image', $term); ?>" />
<?php endif; ?>

<?php if (get_field('social_network', $term) == 1) : ?>
    <meta property="og:title" content="<?php the_field('og_title', $term); ?>" />
    <meta property="twitter:title" content="<?php the_field('twitter_title', $term); ?>" />
    <meta property="og:description" content="<?php the_field('og_description', $term); ?>" />
    <meta property="twitter:description" content="<?php the_field('twitter_description', $term); ?>" />
<?php else : ?>
    <meta property="og:title" content="<?php the_field('title', $term); ?>" />
    <meta property="twitter:title" content="<?php the_field('title', $term); ?>" />
    <meta property="og:description" content="<?php the_field('metadescription', $term); ?>" />
    <meta property="twitter:description" content="<?php the_field('metadescription', $term); ?>" />
<?php endif; ?>

<meta property="og:url" content="<?php echo get_permalink(); ?>" />
<meta property="twitter:url" content="<?php echo get_permalink(); ?>" />

    <!-- Ask what is indexifembedded -->

    <meta property="og:type" content="website" />
    <meta property="twitter:card" content="summary_large_image" />

    <meta name="twitter:site" content="@climbthesearches" />
    <meta name="twitter:creator" content="@irmincorona" />

    <!--
    <meta name="robots" content="unavailable_after: 2020-09-21" />
    <meta name="robots" content="all" />
    <meta name="robots" content="max-image-preview:standard" />
    <meta name="robots" content="index, nofollow" />
    <meta name="robots" content="index, follow" />
    <meta name="robots" content="none" />
    <meta name="robots" content="max-snippet:-1" /> -- cabe todo el snippet completo pero si es 0 = nosnippet
            
    -->