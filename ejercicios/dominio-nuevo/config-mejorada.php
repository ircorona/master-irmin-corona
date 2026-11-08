<?php
// La misma idea, sin las trampas del dominio nuevo:
//  - normaliza el Host: minúsculas, sin puerto, sin punto final y sin «www.»;
//  - admite una lista de dominios de producción (el viejo y el nuevo durante la migración);
//  - no da aviso por línea de comandos (sin HTTP_HOST).
// Las credenciales de producción no van en este fichero: salen de otro fuera del repositorio.
// Para que el entorno no dependa del visitante, ver config-seguro.php en ../clonar-base-de-datos/.

$produccion = ['dominioproduccion.com', 'dominionuevo.com'];

$host = strtolower($_SERVER['HTTP_HOST'] ?? '');
$host = preg_replace('/:\d+$/', '', $host);   // dominio.com:8443 -> dominio.com
$host = rtrim($host, '.');                    // dominio.com.     -> dominio.com
$host = preg_replace('/^www\./', '', $host);  // www.dominio.com  -> dominio.com

if (in_array($host, $produccion, true)) {
    // En el servidor real: require '/home/usuario/secretos/db.php'; (fuera de public_html y de Git)
    define('DB_NAME', 'nombre_ddbbproduccion');
    define('DB_USER', 'usuarioproduccion');
    define('DB_PASSWORD', getenv('DB_PASSWORD') ?: 'passproduccion');
} else {
    define('DB_NAME', 'nombreddbb_local');
    define('DB_USER', 'root');
    define('DB_PASSWORD', '');
}
