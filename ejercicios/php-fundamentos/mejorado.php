<?php
// La misma página con lo que le falta para SEO. Los datos irían en una base
// de datos; aquí siguen en un array para comparar con clase.php.
$posts = [
    ['slug' => 'post-1', 'titulo' => 'Post 1 - Título'],
    ['slug' => 'post-2', 'titulo' => 'Post 2 - Título'],
    ['slug' => 'post-3', 'titulo' => 'Post 3 - Título'],
    ['slug' => 'post-4', 'titulo' => 'Post 4 - Título'],
    ['slug' => 'post-5', 'titulo' => 'Post 5 - Título'],
];
$titulo = 'Hola desde PHP';

function e(string $texto): string
{
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?></title>
    <meta name="description" content="Primera página en PHP: los últimos posts del blog.">
    <link rel="canonical" href="https://master-irmin-corona.test/ejercicios/php-fundamentos/mejorado.php">
</head>
<body>
    <h1>Mi primera página en PHP</h1>

    <ul>
    <?php foreach ($posts as $post): ?>
        <li><a href="/blog/<?= e($post['slug']) ?>/"><?= e($post['titulo']) ?></a></li>
    <?php endforeach; ?>
    </ul>
</body>
</html>
