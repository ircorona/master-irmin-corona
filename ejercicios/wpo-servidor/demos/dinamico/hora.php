<?php
// Página "pesada": simula 300 ms de consultas a la base de datos.
// Como cualquier PHP, no envía Last-Modified ni ETag.
usleep(300000);
?>
<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><title>Página dinámica</title></head>
<body>
<p>Generada: <?= sprintf('%.4f', microtime(true)) ?></p>
</body>
</html>
