<?php
/**
 * Plugin Name:       WP PostgreSQL Database
 * Plugin URI:        https://github.com/your-username/wp-pgsql-database
 * Description:       Adds PostgreSQL database driver support to WordPress via a db.php drop-in, enabling WordPress to run on PostgreSQL without code changes.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Your Name
 * Author URI:        https://yourwebsite.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-pgsql-database
 * Domain Path:       /languages
 *
 * @package WP_PgSQL_Database
 */

declare( strict_types=1 );

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin version constant.
define( 'WP_PGSQL_DB_VERSION', '1.0.0' );

// Plugin root path and URL.
define( 'WP_PGSQL_DB_PATH', plugin_dir_path( __FILE__ ) );
define( 'WP_PGSQL_DB_URL', plugin_dir_url( __FILE__ ) );

// Drop-in paths.
define( 'WP_PGSQL_DB_DROPIN_SOURCE', WP_PGSQL_DB_PATH . 'db.copy' );
define( 'WP_PGSQL_DB_DROPIN_DEST', WP_CONTENT_DIR . '/db.php' );

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap the plugin.
add_action(
	'plugins_loaded',
	function (): void {
		\WP_PgSQL_Database\WP_PgSQL_Database::get_instance();
	}
);

// Activation / deactivation hooks.
register_activation_hook( __FILE__, [ \WP_PgSQL_Database\Migration\Installer::class, 'activate' ] );
register_deactivation_hook( __FILE__, [ \WP_PgSQL_Database\Migration\Installer::class, 'deactivate' ] );
