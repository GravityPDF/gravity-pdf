<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * This has been slightly modified (to read environment variables) for use in Docker.
 *
 * @link    https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// IMPORTANT: this file needs to stay in-sync with https://github.com/WordPress/WordPress/blob/master/wp-config-sample.php
// (it gets parsed by the upstream wizard in https://github.com/WordPress/WordPress/blob/f27cb65e1ef25d11b535695a660e7282b98eb742/wp-admin/setup-config.php#L356-L392)

// a helper function to lookup "env_FILE", "env", then fallback
if ( ! function_exists( 'getenv_docker' ) ) {
	// https://github.com/docker-library/wordpress/issues/588 (WP-CLI will load this file 2x)
	function getenv_docker( $env, $default ) {
		if ( $fileEnv = getenv( $env . '_FILE' ) ) {
			return rtrim( file_get_contents( $fileEnv ), "\r\n" );
		} elseif ( ( $val = getenv( $env ) ) !== false ) {
			return $val;
		} else {
			return $default;
		}
	}
}

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', getenv_docker( 'WORDPRESS_DB_NAME', 'wordpress' ) );

/** Database username */
define( 'DB_USER', getenv_docker( 'WORDPRESS_DB_USER', 'example username' ) );

/** Database password */
define( 'DB_PASSWORD', getenv_docker( 'WORDPRESS_DB_PASSWORD', 'example password' ) );

/**
 * Docker image fallback values above are sourced from the official WordPress installation wizard:
 * https://github.com/WordPress/WordPress/blob/1356f6537220ffdc32b9dad2a6cdbe2d010b7a88/wp-admin/setup-config.php#L224-L238
 * (However, using "example username" and "example password" in your database is strongly discouraged.  Please use strong, random credentials!)
 */

/** Database hostname */
define( 'DB_HOST', getenv_docker( 'WORDPRESS_DB_HOST', 'mysql' ) );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', getenv_docker( 'WORDPRESS_DB_CHARSET', 'utf8' ) );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', getenv_docker( 'WORDPRESS_DB_COLLATE', '' ) );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY', getenv_docker( 'WORDPRESS_AUTH_KEY', '5b55eccbe48bbe154df6c78be8a9ddc8badffc11' ) );
define( 'SECURE_AUTH_KEY', getenv_docker( 'WORDPRESS_SECURE_AUTH_KEY', '437da7392c518609ce51e442f51cc6703d1531b2' ) );
define( 'LOGGED_IN_KEY', getenv_docker( 'WORDPRESS_LOGGED_IN_KEY', '5252a278cc1f656c6b757f52c9437019172547c0' ) );
define( 'NONCE_KEY', getenv_docker( 'WORDPRESS_NONCE_KEY', '2df5e822b8d63af679ac8162dd295280bf9197e7' ) );
define( 'AUTH_SALT', getenv_docker( 'WORDPRESS_AUTH_SALT', '8447685b361d578953aa59374bb83d7aadb1c628' ) );
define( 'SECURE_AUTH_SALT', getenv_docker( 'WORDPRESS_SECURE_AUTH_SALT', '8ca985cafecda23a69052a4121224f104f305aa2' ) );
define( 'LOGGED_IN_SALT', getenv_docker( 'WORDPRESS_LOGGED_IN_SALT', 'ada0e8de86483e0bd481e08893621ece25807721' ) );
define( 'NONCE_SALT', getenv_docker( 'WORDPRESS_NONCE_SALT', '6cb425be8ca3eb2fd3ec199c7aa6062e3dfc00da' ) );
// (See also https://wordpress.stackexchange.com/a/152905/199287)

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = getenv_docker( 'WORDPRESS_TABLE_PREFIX', 'wp_' );

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'FS_METHOD', 'direct' );
define( 'SCRIPT_DEBUG', false );
define( 'WP_ENVIRONMENT_TYPE', 'local' );
define( 'WP_PHP_BINARY', 'php' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Test Blog' );
define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_SITEURL', 'http://example.org' );
define( 'WP_HOME', 'http://example.org' );
define( 'WP_DEBUG_DISPLAY', false );
define( 'WP_DEBUG_LOG', '/var/www/html/wp-content/plugins/gravity-pdf/tmp/debug.log' );
define( 'WP_DEBUG', true );

/* Add any custom values between this line and the "stop editing" line. */

// If we're behind a proxy server and using HTTPS, we need to alert WordPress of that fact
// see also https://wordpress.org/support/article/administration-over-ssl/#using-a-reverse-proxy
if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && strpos( $_SERVER['HTTP_X_FORWARDED_PROTO'], 'https' ) !== false ) {
	$_SERVER['HTTPS'] = 'on';
}
// (we include this by default because reverse proxying is extremely common in container environments)

if ( $configExtra = getenv_docker( 'WORDPRESS_CONFIG_EXTRA', '' ) ) {
	eval( $configExtra );
}

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/var/www/html/' );
	define( 'WP_DEFAULT_THEME', 'default' );
}