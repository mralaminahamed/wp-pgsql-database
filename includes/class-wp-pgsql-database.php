<?php
/**
 * Main plugin orchestrator class.
 *
 * @package WP_PgSQL_Database
 * @since   1.0.0
 */

declare( strict_types=1 );

namespace WP_PgSQL_Database;

use WP_PgSQL_Database\Admin\WP_PgSQL_Admin;
use WP_PgSQL_Database\Admin\WP_PgSQL_Health_Check;
use WP_PgSQL_Database\Compat\WP_PgSQL_Diagnostics;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WP_PgSQL_Database
 *
 * Singleton orchestrator. Registers all subsystem hooks and manages
 * plugin lifecycle from the plugins_loaded action onward.
 */
final class WP_PgSQL_Database {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	private string $version;

	/**
	 * Private constructor — use get_instance().
	 */
	private function __construct() {
		$this->version = WP_PGSQL_DB_VERSION;
	}

	/**
	 * Return the singleton instance, creating it on first call.
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
	 * Register all plugin hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// Boot admin subsystems only in the admin context.
		if ( is_admin() ) {
			add_action( 'init', array( $this, 'boot_admin' ), 20 );
		}

		// Register Site Health integration.
		add_action( 'init', array( $this, 'boot_health_check' ), 20 );

		// Diagnostics are loaded when WP_DEBUG is active.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			add_action( 'init', array( $this, 'boot_diagnostics' ), 30 );
		}
	}

	/**
	 * Load plugin text domain for i18n.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'wp-pgsql-database',
			false,
			dirname( plugin_basename( WP_PGSQL_DB_PATH . 'wp-pgsql-database.php' ) ) . '/languages'
		);
	}

	/**
	 * Enqueue admin-side CSS and JS assets.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_admin_assets( string $hook_suffix ): void {
		// Only enqueue on our own admin pages.
		if ( false === strpos( $hook_suffix, 'wp-pgsql-database' ) ) {
			return;
		}

		wp_enqueue_style(
			'wp-pgsql-database-admin',
			WP_PGSQL_DB_URL . 'assets/css/admin.css',
			array(),
			$this->version
		);

		wp_enqueue_script(
			'wp-pgsql-database-admin',
			WP_PGSQL_DB_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		wp_localize_script(
			'wp-pgsql-database-admin',
			'wpPgsqlDatabase',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'wp_pgsql_database_admin' ),
				'i18n'    => array(
					'connectionOk'     => __( 'Connection successful.', 'wp-pgsql-database' ),
					'connectionFailed' => __( 'Connection failed.', 'wp-pgsql-database' ),
					'saving'           => __( 'Saving…', 'wp-pgsql-database' ),
					'saved'            => __( 'Settings saved.', 'wp-pgsql-database' ),
				),
			)
		);
	}

	/**
	 * Initialise admin page subsystem.
	 *
	 * @return void
	 */
	public function boot_admin(): void {
		WP_PgSQL_Admin::get_instance();
	}

	/**
	 * Initialise WordPress Site Health integration.
	 *
	 * @return void
	 */
	public function boot_health_check(): void {
		WP_PgSQL_Health_Check::get_instance();
	}

	/**
	 * Initialise query diagnostics (WP_DEBUG only).
	 *
	 * @return void
	 */
	public function boot_diagnostics(): void {
		WP_PgSQL_Diagnostics::get_instance();
	}

	/**
	 * Return the current plugin version.
	 *
	 * @return string
	 */
	public function get_version(): string {
		return $this->version;
	}

	/**
	 * Prevent cloning of the singleton.
	 *
	 * @return void
	 */
	private function __clone() {}
}
