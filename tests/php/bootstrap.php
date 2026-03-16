<?php
/**
 * Bootstrap file for PHPUnit tests.
 *
 * @package WP_PgSQL_Database\Tests
 */

declare( strict_types=1 );

define( 'WP_PGSQL_DB_PLUGIN_DIR', dirname( __DIR__, 2 ) );

require_once WP_PGSQL_DB_PLUGIN_DIR . '/vendor/autoload.php';

$_tests_dir = getenv( 'WP_TESTS_DIR' ) ? getenv( 'WP_TESTS_DIR' ) : getenv( 'WP_PHPUNIT__DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

define( 'WP_TESTS_DIR', $_tests_dir );

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find {$_tests_dir}/includes/functions.php, have you run bin/install-wp-tests.sh?" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

function _manually_load_wp_pgsql_database(): void {
	require WP_PGSQL_DB_PLUGIN_DIR . '/wp-pgsql-database.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_wp_pgsql_database' );

require $_tests_dir . '/includes/bootstrap.php';
