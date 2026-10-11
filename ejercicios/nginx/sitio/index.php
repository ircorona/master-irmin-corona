<?php
// Página de inicio y "front controller": no cambia el código de respuesta
echo 'INICIO ' . htmlspecialchars($_SERVER['REQUEST_URI']);
