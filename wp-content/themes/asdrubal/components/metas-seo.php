<?php
$term = get_queried_object();
?>

<!-- metas-seo.php -->

<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta charset="UTF-8">
<!-- end of metas-seo.php -->

<!-- <meta http-equiv="refresh " content="30; url=https://google.com/" /> -->
<!-- <nonscript> <meta http-equiv="refresh " content="3" /> </nonscript>-->
<?php the_field('custom_meta', $term);?>

<meta name="robots" content="" />
    <title></title>
    <meta property="og:title" content=""/>
    <meta property="twitter:title" content="" />

    <meta property="og:url" content="" />
    <meta property="twitter:url" content="" />

    <meta property="og:image" content="" />
    <meta property="og:image:secure_url" content="" />
    <meta property="og:image:alt" content=""/>
    <meta property="twitter:image" content="" />


    <meta name="description" content="" />
    <meta name="og:description" content="" />
    <meta name="twitter:description" content="" />

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