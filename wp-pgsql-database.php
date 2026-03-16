<?php
/**
 * WP PostgreSQL Database
 *
 * Adds PostgreSQL database driver support to WordPress via a db.php drop-in,
 * enabling WordPress to run on PostgreSQL without any code changes to core,
 * plugins, or themes. Includes MySQL to PostgreSQL query translation.
 *
 * @link              https://github.com/mralaminahamed/wp-pgsql-database
 * @since             1.0.0
 * @package           WP_PgSQL_Database
 *
 * @wordpress-plugin
 * Plugin Name:       WP PostgreSQL Database
 * Plugin URI:        https://github.com/mralaminahamed/wp-pgsql-database
 * Description:       Adds PostgreSQL database driver support to WordPress via a db.php drop-in, enabling WordPress to run on PostgreSQL without code changes.
 * Version:           1.0.0
 * Author:            Al Amin Ahamed
 * Author URI:        https://alaminahamed.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wp-pgsql-database
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Tested up to:      6.9
 * Requires PHP:      7.4
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

/**
 * Bootstrap the plugin and return the instance.
 *
 * @return WP_PgSQL_Database\WP_PgSQL_Database
 */
function wp_pgsql_database() {
	return WP_PgSQL_Database\WP_PgSQL_Database::get_instance();
}

// Bootstrap the plugin.
wp_pgsql_database()->init();

// Activation / deactivation hooks.
register_activation_hook( __FILE__, array( WP_PgSQL_Database\Migration\WP_PgSQL_Installer::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( WP_PgSQL_Database\Migration\WP_PgSQL_Installer::class, 'deactivate' ) );
