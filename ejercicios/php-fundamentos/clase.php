<?php
    // Código de la clase 03, copiado de la foto. Las líneas 1-27 son literales
    // (incluido el 'Post 4' de arriba, que Carlos estaba escribiendo).
    // El segundo <div> sale cortado en la foto: el foreach es una reconstrucción.
    $posts = [
        'Post 4 - Titulo',
        'Post 1 - Titulo',
        'Post 2 - Titulo',
        'Post 3 - Titulo',
        'Post 4 - Titulo',
        'Post 5 - Titulo',
    ];
    $test = "Hola desde PHP";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Hola desde PHP</title>
</head>
<body>
<h1>Mi primera página en PHP</h1>

<div>
    <?php
        echo $test;
    ?>
</div>

<div>
    <?php foreach ($posts as $post) { ?>
        <p><?php echo $post; ?></p>
    <?php } ?>
</div>
</body>
</html>
