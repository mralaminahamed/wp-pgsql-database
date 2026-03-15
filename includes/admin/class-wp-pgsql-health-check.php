<?php
/**
 * WordPress Site Health integration.
 *
 * @package WP_PgSQL_Database\Admin
 * @since   1.0.0
 */

declare( strict_types=1 );

namespace WP_PgSQL_Database\Admin;

use WP_PgSQL_Database\Migration\WP_PgSQL_Installer;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WP_PgSQL_Health_Check
 *
 * Integrates with the WordPress Site Health API (Tools › Site Health)
 * to surface PostgreSQL driver status natively.
 */
class WP_PgSQL_Health_Check {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Private constructor.
	 */
	private function __construct() {
		add_filter( 'site_status_tests', [ $this, 'register_tests' ] );
		add_filter( 'debug_information', [ $this, 'add_debug_info' ] );
	}

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
	 * Register Site Health tests.
	 *
	 * @param array<string, mixed> $tests Existing tests.
	 * @return array<string, mixed>
	 */
	public function register_tests( array $tests ): array {
		$tests['direct']['wp_pgsql_dropin'] = [
			'label' => __( 'PostgreSQL drop-in', 'wp-pgsql-database' ),
			'test'  => [ $this, 'test_dropin' ],
		];

		$tests['direct']['wp_pgsql_connection'] = [
			'label' => __( 'PostgreSQL connection', 'wp-pgsql-database' ),
			'test'  => [ $this, 'test_connection' ],
		];

		return $tests;
	}

	/**
	 * Test whether the drop-in is installed and current.
	 *
	 * @return array<string, mixed>
	 */
	public function test_dropin(): array {
		$active  = WP_PgSQL_Installer::is_dropin_active();
		$current = WP_PgSQL_Installer::is_dropin_current();

		if ( ! $active ) {
			return [
				'label'       => __( 'PostgreSQL drop-in is not installed', 'wp-pgsql-database' ),
				'status'      => 'critical',
				'badge'       => [ 'label' => __( 'Database', 'wp-pgsql-database' ), 'color' => 'red' ],
				'description' => __( 'The wp-content/db.php drop-in is missing. PostgreSQL driver is inactive.', 'wp-pgsql-database' ),
				'actions'     => sprintf(
					'<a href="%s">%s</a>',
					esc_url( admin_url( 'tools.php?page=wp-pgsql-database' ) ),
					esc_html__( 'Install drop-in', 'wp-pgsql-database' )
				),
				'test'        => 'wp_pgsql_dropin',
			];
		}

		if ( ! $current ) {
			return [
				'label'       => __( 'PostgreSQL drop-in needs updating', 'wp-pgsql-database' ),
				'status'      => 'recommended',
				'badge'       => [ 'label' => __( 'Database', 'wp-pgsql-database' ), 'color' => 'orange' ],
				'description' => __( 'A newer version of the db.php drop-in is available.', 'wp-pgsql-database' ),
				'actions'     => sprintf(
					'<a href="%s">%s</a>',
					esc_url( admin_url( 'tools.php?page=wp-pgsql-database' ) ),
					esc_html__( 'Update drop-in', 'wp-pgsql-database' )
				),
				'test'        => 'wp_pgsql_dropin',
			];
		}

		return [
			'label'       => __( 'PostgreSQL drop-in is active and current', 'wp-pgsql-database' ),
			'status'      => 'good',
			'badge'       => [ 'label' => __( 'Database', 'wp-pgsql-database' ), 'color' => 'blue' ],
			'description' => __( 'The wp-content/db.php drop-in is installed and up to date.', 'wp-pgsql-database' ),
			'actions'     => '',
			'test'        => 'wp_pgsql_dropin',
		];
	}

	/**
	 * Test whether the PostgreSQL connection is live.
	 *
	 * @return array<string, mixed>
	 */
	public function test_connection(): array {
		global $wpdb;

		$is_pgsql = $wpdb instanceof \WP_PgSQL_Database\Database\WP_PgSQL_Db;

		if ( ! $is_pgsql ) {
			return [
				'label'       => __( 'Not running on PostgreSQL driver', 'wp-pgsql-database' ),
				'status'      => 'recommended',
				'badge'       => [ 'label' => __( 'Database', 'wp-pgsql-database' ), 'color' => 'orange' ],
				'description' => __( 'WordPress is using the default MySQL driver. Define DB_ENGINE=pgsql in wp-config.php to activate PostgreSQL.', 'wp-pgsql-database' ),
				'actions'     => '',
				'test'        => 'wp_pgsql_connection',
			];
		}

		$connected = $wpdb->check_connection( false );

		if ( ! $connected ) {
			return [
				'label'       => __( 'PostgreSQL connection is unavailable', 'wp-pgsql-database' ),
				'status'      => 'critical',
				'badge'       => [ 'label' => __( 'Database', 'wp-pgsql-database' ), 'color' => 'red' ],
				'description' => __( 'Could not reach the PostgreSQL server. Check DB_HOST, DB_USER, DB_PASSWORD, and DB_NAME.', 'wp-pgsql-database' ),
				'actions'     => '',
				'test'        => 'wp_pgsql_connection',
			];
		}

		return [
			'label'       => __( 'PostgreSQL connection is active', 'wp-pgsql-database' ),
			'status'      => 'good',
			'badge'       => [ 'label' => __( 'Database', 'wp-pgsql-database' ), 'color' => 'blue' ],
			'description' => sprintf(
				/* translators: %s: server version string */
				__( 'Connected to PostgreSQL. Server: %s', 'wp-pgsql-database' ),
				esc_html( $wpdb->db_version() )
			),
			'actions'     => '',
			'test'        => 'wp_pgsql_connection',
		];
	}

	/**
	 * Add driver info to the Site Health debug information panel.
	 *
	 * @param array<string, mixed> $info Existing debug info.
	 * @return array<string, mixed>
	 */
	public function add_debug_info( array $info ): array {
		global $wpdb;

		$info['wp-pgsql-database'] = [
			'label'  => __( 'PostgreSQL Database Driver', 'wp-pgsql-database' ),
			'fields' => [
				'plugin_version' => [
					'label' => __( 'Plugin version', 'wp-pgsql-database' ),
					'value' => WP_PGSQL_DB_VERSION,
				],
				'dropin_active' => [
					'label' => __( 'Drop-in active', 'wp-pgsql-database' ),
					'value' => WP_PgSQL_Installer::is_dropin_active() ? __( 'Yes', 'wp-pgsql-database' ) : __( 'No', 'wp-pgsql-database' ),
				],
				'db_engine' => [
					'label' => __( 'DB_ENGINE', 'wp-pgsql-database' ),
					'value' => defined( 'DB_ENGINE' ) ? DB_ENGINE : __( 'Not defined', 'wp-pgsql-database' ),
				],
				'server_version' => [
					'label' => __( 'PostgreSQL version', 'wp-pgsql-database' ),
					'value' => ( $wpdb instanceof \WP_PgSQL_Database\Database\WP_PgSQL_Db ) ? $wpdb->db_version() : __( 'N/A', 'wp-pgsql-database' ),
				],
			],
		];

		return $info;
	}

	/**
	 * Prevent cloning.
	 *
	 * @return void
	 */
	private function __clone() {}
}
