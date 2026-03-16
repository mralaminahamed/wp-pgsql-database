<?php
/**
 * Plugin installer and drop-in lifecycle manager.
 *
 * @package WP_PgSQL_Database\Migration
 * @since   1.0.0
 */

declare( strict_types=1 );

namespace WP_PgSQL_Database\Migration;

use WP_PgSQL_Database\WP_PgSQL_Filesystem;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WP_PgSQL_Installer
 *
 * Manages plugin activation and deactivation, including installation and
 * removal of the db.php drop-in in wp-content/.
 */
class WP_PgSQL_Installer {

	/**
	 * Filesystem instance.
	 *
	 * @var WP_PgSQL_Filesystem|null
	 */
	private static ?WP_PgSQL_Filesystem $filesystem = null;

	/**
	 * Get the filesystem instance.
	 *
	 * @return WP_PgSQL_Filesystem
	 */
	private static function get_filesystem(): WP_PgSQL_Filesystem {
		if ( null === self::$filesystem ) {
			self::$filesystem = WP_PgSQL_Filesystem::get_instance();
		}

		return self::$filesystem;
	}

	/**
	 * Plugin activation callback.
	 *
	 * Copies the db.copy template to wp-content/db.php and stores
	 * the current plugin version in the database.
	 *
	 * @return void
	 */
	public static function activate(): void {
		self::install_dropin();
		update_option( 'wp_pgsql_db_version', WP_PGSQL_DB_VERSION, false );
		update_option( 'wp_pgsql_db_activated', current_time( 'mysql' ), false );
	}

	/**
	 * Plugin deactivation callback.
	 *
	 * Removes the db.php drop-in to restore default MySQL connectivity.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		self::remove_dropin();
	}

	/**
	 * Install the db.php drop-in.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function install_dropin(): bool {
		if ( ! defined( 'WP_PGSQL_DB_DROPIN_SOURCE' ) || ! defined( 'WP_PGSQL_DB_DROPIN_DEST' ) ) {
			return false;
		}

		$fs = self::get_filesystem();

		if ( ! $fs->exists( WP_PGSQL_DB_DROPIN_SOURCE ) ) {
			return false;
		}

		// If a db.php already exists and was not installed by this plugin, do not overwrite.
		if ( $fs->exists( WP_PGSQL_DB_DROPIN_DEST ) && ! self::is_our_dropin() ) {
			return false;
		}

		$result = $fs->copy( WP_PGSQL_DB_DROPIN_SOURCE, WP_PGSQL_DB_DROPIN_DEST, true );

		if ( $result ) {
			update_option( 'wp_pgsql_db_dropin_installed', true, false );
		}

		return $result;
	}

	/**
	 * Remove the db.php drop-in installed by this plugin.
	 *
	 * @return bool True on success (or if no drop-in present), false on failure.
	 */
	public static function remove_dropin(): bool {
		$fs = self::get_filesystem();

		if ( ! $fs->exists( WP_PGSQL_DB_DROPIN_DEST ) ) {
			delete_option( 'wp_pgsql_db_dropin_installed' );

			return true;
		}

		// Only remove a drop-in that we installed.
		if ( ! self::is_our_dropin() ) {
			return false;
		}

		$result = $fs->delete( WP_PGSQL_DB_DROPIN_DEST );

		if ( $result ) {
			delete_option( 'wp_pgsql_db_dropin_installed' );
		}

		return $result;
	}

	/**
	 * Check whether the currently installed db.php belongs to this plugin.
	 *
	 * @return bool
	 */
	public static function is_our_dropin(): bool {
		$fs = self::get_filesystem();

		if ( ! $fs->exists( WP_PGSQL_DB_DROPIN_DEST ) ) {
			return false;
		}

		$content = $fs->get_contents( WP_PGSQL_DB_DROPIN_DEST );

		return false !== $content && str_contains( $content, 'WP PostgreSQL Database Drop-in' );
	}

	/**
	 * Determine whether the drop-in is currently active.
	 *
	 * @return bool
	 */
	public static function is_dropin_active(): bool {
		$fs = self::get_filesystem();

		return $fs->exists( WP_PGSQL_DB_DROPIN_DEST ) && self::is_our_dropin();
	}

	/**
	 * Check whether the drop-in is up to date with the plugin's db.copy.
	 *
	 * @return bool True if drop-in matches the current template.
	 */
	public static function is_dropin_current(): bool {
		if ( ! self::is_dropin_active() ) {
			return false;
		}

		$fs = self::get_filesystem();

		$installed = $fs->get_contents( WP_PGSQL_DB_DROPIN_DEST );
		$template  = $fs->get_contents( WP_PGSQL_DB_DROPIN_SOURCE );

		if ( false === $installed || false === $template ) {
			return false;
		}

		return md5( $installed ) === md5( $template );
	}
}
