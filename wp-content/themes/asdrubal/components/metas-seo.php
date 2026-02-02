<?php
if ( is_category() || is_tag() || is_tax() ) {
    $term = get_queried_object();
} else {
    $term = get_the_ID();
}

$protocol = isset($_SERVER["HTTPS"]) ? 'https' : 'http';
$url_sin_string = $protocol . '://' . $_SERVER['HTTP_HOST'] . strtok($_SERVER["REQUEST_URI"], '?');
?>

<!-- metas-seo.php -->

<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta charset="UTF-8">
<?php if (get_field('metarobots', $term)) : ?>
    <meta name="robots" content="<?php echo esc_attr( implode(', ', get_field('metarobots', $term)) ); ?>" />
<?php endif; ?>

<!-- Custom Meta Tags -->
<?php the_field('custom_meta', $term); ?>

<title><?php the_field('title', $term); ?></title>


<link rel="canonical" href="<?php if (get_field('canonical', $term)) { the_field('canonical', $term); } else { echo $url_sin_string; } ?>" />

<?php
$og_image = get_field('og_image', $term) ? get_field('og_image', $term) : get_template_directory_uri() . '/asdrubal.jpg';
?>
    <meta property="og:image" content="<?php echo $og_image; ?>" />
    <meta property="og:image:secure_url" content="<?php echo $og_image; ?>" />
    <meta property="og:image:alt" content="<?php the_field('title', $term); ?>" />
    <meta property="twitter:image" content="<?php echo $og_image; ?>" />

<?php if (in_category('cars')) :
    $cars_desc = 'My memorable experience with a ' . get_field('brand', $term) . ' ' . get_the_title() . ' - ' . get_field('hp', $term) . 'HP, ' . get_field('fuel', $term) . ', $' . get_field('price', $term);
?>
    <meta name="description" content="<?php echo esc_attr($cars_desc); ?>" />
    <meta property="og:description" content="<?php echo esc_attr($cars_desc); ?>" />
    <meta property="twitter:description" content="<?php echo esc_attr($cars_desc); ?>" />
<?php else : ?>
    <meta name="description" content="<?php the_field('meta_description', $term); ?>" />
    <meta property="og:description" content="<?php if (get_field('og_description', $term)) { the_field('og_description', $term); } else { the_field('meta_description', $term); } ?>" />
    <meta property="twitter:description" content="<?php if (get_field('twitter_description', $term)) { the_field('twitter_description', $term); } elseif (get_field('og_description', $term)) { the_field('og_description', $term); } else { the_field('meta_description', $term); } ?>" />
<?php endif; ?>

    <meta property="og:title" content="<?php if (get_field('og_title', $term)) { the_field('og_title', $term); } else { the_field('title', $term); } ?>" />
    <meta property="twitter:title" content="<?php if (get_field('twitter_title', $term)) { the_field('twitter_title', $term); } elseif (get_field('og_title', $term)) { the_field('og_title', $term); } else { the_field('title', $term); } ?>" />

<meta property="og:url" content="<?php if (get_field('canonical', $term)) { the_field('canonical', $term); } else { echo $url_sin_string; } ?>" />
<meta property="twitter:url" content="<?php if (get_field('canonical', $term)) { the_field('canonical', $term); } else { echo $url_sin_string; } ?>" />



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