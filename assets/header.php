<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="/ejemplo/CSS/style.css">
       <?php 
        $uri = $_SERVER['REQUEST_URI'];

        // Limpiar la URI (quitar .php si existe)
        $uri_clean = str_replace('.php', '', $uri);

        switch (true) {
            case strpos($uri_clean, '/contact') !== false:
                $titulo = "He conseguido poner el título en base a una variable de la URL";
                break;
            case strpos($uri_clean, '/about-me') !== false:
                $titulo = "About Me - My PHP Website";
                break;
            case strpos($uri_clean, '/folder/file-folder') !== false:
                $titulo = "My Projects - My PHP Website";
                break;
            case $uri_clean == '/' || strpos($uri_clean, '/index') !== false:
                $titulo = "Home - My PHP Website";
                break;
            default:
                $titulo = "My PHP Website";
        }
        ?>

        <!-- CUSTOM FONTS 
        <link href="/CSS/fonts.css" rel="stylesheet"> -->

        <!-- GOOGLE FONTS -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Catamaran:wght@100..900&display=swap" rel="stylesheet">
        <!-- INTERNAL CSS -->
        <style>
            .internal-example {
                background: lightgreen;
                padding: 20px;
                margin: 15px 0;
                border: 3px solid green;
                font-size: 1.2rem;
            }
        </style>
        <title>
            <?php 
            if (empty($titulo)) {
                $titulo = "Default Title - My PHP Website";
            }
            echo $titulo; 
            ?>         
        </title>
    </head>
    <body>
        <header>
            <nav>
                <ul>
                    <li><a href="/">Home</a></li>
                    <li><a href="/about-me">About Me</a></li>
                    <li><a href="/contact">Contact</a></li>
                    <li><a href="/folder/file-folder">Projects</a></li>
                    <li><a href="https://github.com/ircorona">My GitHub</a></li>
                </ul>
            </nav>
        </header>