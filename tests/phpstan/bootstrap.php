<?php
/**
 * PHPStan bootstrap — provides stubs for WordPress globals and constants
 * so static analysis can resolve types without a full WordPress install.
 *
 * @package WP_PgSQL_Database
 */

declare( strict_types=1 );

// Plugin constants required for analysis.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/wordpress/' );
}

if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
}

if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
	define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
}

if ( ! defined( 'WPMU_PLUGIN_DIR' ) ) {
	define( 'WPMU_PLUGIN_DIR', WP_CONTENT_DIR . '/mu-plugins' );
}

if ( ! defined( 'WP_PGSQL_DB_VERSION' ) ) {
	define( 'WP_PGSQL_DB_VERSION', '1.0.0' );
}

if ( ! defined( 'WP_PGSQL_DB_PATH' ) ) {
	define( 'WP_PGSQL_DB_PATH', dirname( __DIR__ ) . 'bootstrap.php/' );
}

if ( ! defined( 'WP_PGSQL_DB_URL' ) ) {
	define( 'WP_PGSQL_DB_URL', 'https://example.com/wp-content/plugins/wp-pgsql-database/' );
}

if ( ! defined( 'WP_PGSQL_DB_DROPIN_SOURCE' ) ) {
	define( 'WP_PGSQL_DB_DROPIN_SOURCE', WP_PGSQL_DB_PATH . 'db.copy' );
}

if ( ! defined( 'WP_PGSQL_DB_DROPIN_DEST' ) ) {
	define( 'WP_PGSQL_DB_DROPIN_DEST', WP_CONTENT_DIR . '/db.php' );
}
