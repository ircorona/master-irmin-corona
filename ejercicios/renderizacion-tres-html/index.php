<?php
// La demo de clase (renderizacion.php), corregida:
// - zona horaria explícita: sin ella PHP usa la de php.ini (UTC en el CLI de Laragon)
// - comparación entre enteros, no entre cadenas
// - tres franjas en vez de "< 5", que daba «Buenos días» solo de 00 a 04
date_default_timezone_set('Europe/Madrid');
$hora = (int) date('G');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Renderización: tres HTML</title>
</head>
<body>

<section>
    <div>
        <p id="servidor"><?php
        if ($hora < 12) {
            echo '¡Buenos días!';
        } elseif ($hora < 20) {
            echo '¡Buenas tardes!';
        } else {
            echo '¡Buenas noches!';
        }
        ?></p>
    </div>

    <p id="test"></p>
</section>

<script>
// Circuito 2: esto no viaja en el HTML, lo escribe el navegador al ejecutar el JS.
// Usa la hora del dispositivo del usuario, no la del servidor.
var h = new Date().getHours();
document.getElementById('test').textContent =
    'JS: son las ' + h + ' h en tu dispositivo';
</script>
</body>
</html>
