<?php
/**
 * WordPress filesystem wrapper.
 *
 * @package WP_PgSQL_Database
 * @since   1.0.0
 */

declare( strict_types=1 );

namespace WP_PgSQL_Database;

use WP_Filesystem_Base;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WP_PgSQL_Filesystem
 *
 * Wrapper around WordPress filesystem API for file operations.
 * Provides a consistent interface for reading, writing, and checking files.
 */
class WP_PgSQL_Filesystem {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Whether the filesystem is initialized.
	 *
	 * @var bool
	 */
	private bool $initialized = false;

	/**
	 * Filesystem credentials.
	 *
	 * @var array<string, mixed>
	 */
	private array $credentials = array();

	/**
	 * Private constructor.
	 */
	private function __construct() {}

	/**
	 * Return the singleton instance.
	 *
	 * @return self
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize the WordPress filesystem.
	 *
	 * @return bool True if initialized successfully.
	 */
	public function init(): bool {
		if ( $this->initialized ) {
			return true;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			return false;
		}

		$credentials = request_filesystem_credentials( self_admin_url() );

		if ( false === $credentials ) {
			return false;
		}

		if ( ! WP_Filesystem( $credentials ) ) {
			return false;
		}

		global $wp_filesystem;

		if ( ! $wp_filesystem instanceof WP_Filesystem_Base ) {
			return false;
		}

		$this->initialized = true;

		return true;
	}

	/**
	 * Check if a file exists.
	 *
	 * @param string $path File path to check.
	 *
	 * @return bool True if file exists.
	 */
	public function exists( string $path ): bool {
		return $this->is_initialized() && $this->get_wpfs()->exists( $path );
	}

	/**
	 * Read a file contents.
	 *
	 * @param string $path File path to read.
	 *
	 * @return string|false File contents or false on failure.
	 */
	public function get_contents( string $path ) {
		if ( ! $this->is_initialized() ) {
			return false;
		}

		return $this->get_wpfs()->get_contents( $path );
	}

	/**
	 * Write contents to a file.
	 *
	 * @param string $path    File path to write to.
	 * @param string $contents Contents to write.
	 *
	 * @return bool True on success.
	 */
	public function put_contents( string $path, string $contents ): bool {
		if ( ! $this->is_initialized() ) {
			return false;
		}

		return $this->get_wpfs()->put_contents( $path, $contents );
	}

	/**
	 * Copy a file.
	 *
	 * @param string $source      Source file path.
	 * @param string $destination Destination file path.
	 * @param bool   $overwrite   Whether to overwrite existing file.
	 *
	 * @return bool True on success.
	 */
	public function copy( string $source, string $destination, bool $overwrite = false ): bool {
		if ( ! $this->is_initialized() ) {
			return false;
		}

		return $this->get_wpfs()->copy( $source, $destination, $overwrite );
	}

	/**
	 * Delete a file.
	 *
	 * @param string $path     File path to delete.
	 * @param bool   $recursive Whether to recursively delete (for directories).
	 *
	 * @return bool True on success.
	 */
	public function delete( string $path, bool $recursive = false ): bool {
		if ( ! $this->is_initialized() ) {
			return false;
		}

		return $this->get_wpfs()->delete( $path, $recursive );
	}

	/**
	 * Check if path is a directory.
	 *
	 * @param string $path Path to check.
	 *
	 * @return bool True if is a directory.
	 */
	public function is_dir( string $path ): bool {
		return $this->is_initialized() && $this->get_wpfs()->is_dir( $path );
	}

	/**
	 * Check if path is a file.
	 *
	 * @param string $path Path to check.
	 *
	 * @return bool True if is a file.
	 */
	public function is_file( string $path ): bool {
		return $this->is_initialized() && $this->get_wpfs()->is_file( $path );
	}

	/**
	 * Get file modification time.
	 *
	 * @param string $path File path.
	 *
	 * @return int|false Modification timestamp or false on failure.
	 */
	public function mtime( string $path ) {
		if ( ! $this->is_initialized() ) {
			return false;
		}

		return $this->get_wpfs()->mtime( $path );
	}

	/**
	 * Get the filesystem base directory.
	 *
	 * @return string Base directory path.
	 */
	public function get_base_dir(): string {
		return $this->get_wpfs()->abspath();
	}

	/**
	 * Check if filesystem is initialized.
	 *
	 * @return bool
	 */
	public function is_initialized(): bool {
		return $this->initialized;
	}

	/**
	 * Get the WordPress filesystem instance.
	 *
	 * @return WP_Filesystem_Base
	 */
	private function get_wpfs(): WP_Filesystem_Base {
		global $wp_filesystem;

		return $wp_filesystem;
	}

	/**
	 * Prevent cloning.
	 *
	 * @return void
	 */
	private function __clone() {}
}
