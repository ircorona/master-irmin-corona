<?php
// Alternativa: el entorno lo decide el SERVIDOR, no la cabecera Host que manda el visitante.
// WordPress (5.5+) lee WP_ENVIRONMENT_TYPE: 'local', 'development', 'staging' o 'production'.
// Se fija fuera del código: SetEnv en el vhost de Apache, fastcgi_param en nginx, o la variable
// de entorno del sistema en local. Si no está, se asume producción: el fallo es "cerrado".

$entorno = getenv('WP_ENVIRONMENT_TYPE') ?: 'production';
if (!in_array($entorno, ['local', 'development', 'staging', 'production'], true)) {
    $entorno = 'production';
}
define('WP_ENVIRONMENT_TYPE', $entorno);

// Las credenciales de cada entorno, fuera del repositorio (aquí, ejemplo con las de la clase).
$credenciales = [
    'production'  => ['*******', '****', '***********'],
    'staging'     => ['staging-*****', '*******', '********'],
    'development' => ['sanchezdonate', 'root', ''],
    'local'       => ['sanchezdonate', 'root', ''],
];
[$db, $usuario, $clave] = $credenciales[$entorno];
define('DB_NAME', $db);
define('DB_USER', $usuario);
define('DB_PASSWORD', $clave);

$depurar = in_array($entorno, ['local', 'development'], true);
define('WP_DEBUG', $depurar);
define('WP_DEBUG_DISPLAY', $depurar);
// En staging se registra, pero fuera de la carpeta pública (por defecto iría a wp-content/debug.log)
define('WP_DEBUG_LOG', $depurar ? true : ($entorno === 'staging' ? dirname(__DIR__) . '/logs/wp-debug.log' : false));
define('SCRIPT_DEBUG', $depurar);
