<?php
// «CONDICIONAL DDBB» de la clase 21, tal cual. Lo único añadido es la primera línea:
// el fragmento de clase usa $dominio_actual sin definirla (en la clase 17 venía de HTTP_HOST).
$dominio_actual = $_SERVER['HTTP_HOST'];

if ($dominio_actual == 'dominioproduccion.com') {
define( 'DB_NAME', 'nombre_ddbbproduccion' );
/** Database username */
define( 'DB_USER', 'usuarioproduccion' );

/** Database password */
define( 'DB_PASSWORD', 'passproduccion' );
;
} else {
define( 'DB_NAME', 'nombreddbb_local' );
/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );
}
