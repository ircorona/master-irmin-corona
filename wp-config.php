<?php
/**
 * The base configuration for WordPress
 */

// ** Database settings ** //
define( 'DB_NAME', 'wordpress' );
define( 'DB_USER', 'root' );
define( 'DB_PASSWORD', '' );
define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

/**
 * Authentication unique keys and salts.
 */
define( 'AUTH_KEY',         'xK7!pQ2@mN9#vL4$bH6%cJ8^dF0&gT3*wE5(yU1)zA' );
define( 'SECURE_AUTH_KEY',  'rM8@nP3#kL6$hJ9%fD2^sA5&wQ7*eY0(tI4)uO1!' );
define( 'LOGGED_IN_KEY',    'vB4#cX7$zN0%mK3^lH6&jF9*gD2(sA5)wE8!qY1@' );
define( 'NONCE_KEY',        'tR6$yU9%iO2^pA5&sD8*fG1(hJ4)kL7!mN0@bV3#' );
define( 'AUTH_SALT',        'wE1%rT4^yU7&iO0*pA3(sD6)fG9!hJ2@kL5#mN8$' );
define( 'SECURE_AUTH_SALT', 'bV2^cX5&zN8*mK1(lH4)jF7!gD0@sA3#wE6$qY9%' );
define( 'LOGGED_IN_SALT',   'nM7&bV0*cX3(zN6)mK9!lH2@jF5#gD8$sA1%wE4^' );
define( 'NONCE_SALT',       'qY3*wE6(rT9)yU2!iO5@pA8#sD1$fG4%hJ7^kL0&' );

/**
 * WordPress database table prefix.
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 */
define( 'WP_DEBUG', true );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
