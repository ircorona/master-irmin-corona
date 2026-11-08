<?php
// Fragmento de wp-config.php de la clase («AVANZADO WP-CONFIG»), copiado tal cual,
// con las credenciales enmascaradas como venían. Elige base de datos y depuración por HTTP_HOST.

$dominio_actual = $_SERVER['HTTP_HOST'];
if ($dominio_actual == 'carlos.sanchezdonate.com') {
define( 'DB_NAME', '*******' );
define( 'DB_USER', '****' );
define( 'DB_PASSWORD', '***********' );
define('WP_POST_REVISIONS', 5);
}
elseif ($dominio_actual == 'test-carlos.sanchezdonate.com'){
define( 'DB_NAME', 'staging-*****' );
define( 'DB_USER', '*******' );
define( 'DB_PASSWORD', '********' );

define('WP_POST_REVISIONS', false);
define('AUTOSAVE_INTERVAL', 999999);
}
else {
define( 'DB_NAME', 'sanchezdonate' );
define( 'DB_USER', 'root' );
define( 'DB_PASSWORD', '' );
}
// Verifica si el sitio no está alojado en producción
if ($dominio_actual !== 'carlos.sanchezdonate.com') {
// Activa el modo de depuración
define('WP_DEBUG', true);
// Guarda los errores en un archivo de registro
define('WP_DEBUG_LOG', true);
// Muestra los errores en la pantalla
define('WP_DEBUG_DISPLAY', true);
// Usa las versiones de desarrollo de los scripts y estilos de core
define('SCRIPT_DEBUG', true);
} else {
define('WP_DEBUG', false);
define('WP_DEBUG_LOG', false);
define('WP_DEBUG_DISPLAY', false);
define('SCRIPT_DEBUG', false);
}
