<?php echo 'ENVIADO ' . $_SERVER['REQUEST_METHOD'] . ' ' . htmlspecialchars($_POST['nombre'] ?? '');
