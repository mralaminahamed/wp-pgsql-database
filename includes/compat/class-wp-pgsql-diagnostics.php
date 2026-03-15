<?php
/**
 * Diagnostics — surfaces driver information on the admin bar in WP_DEBUG mode.
 *
 * @package WP_PgSQL_Database\Compat
 * @since   1.0.0
 */

declare( strict_types=1 );

namespace WP_PgSQL_Database\Compat;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WP_PgSQL_Diagnostics
 *
 * In WP_DEBUG mode, adds a PostgreSQL query count to the admin toolbar
 * and exposes an AJAX endpoint for retrieving the full query log.
 */
class WP_PgSQL_Diagnostics {

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
		add_action( 'admin_bar_menu', [ $this, 'add_toolbar_node' ], 999 );
		add_action( 'wp_ajax_wp_pgsql_query_log', [ $this, 'ajax_query_log' ] );
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
	 * Add a PostgreSQL diagnostics node to the admin toolbar.
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 * @return void
	 */
	public function add_toolbar_node( \WP_Admin_Bar $wp_admin_bar ): void {
		if ( ! is_admin_bar_showing() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		global $wpdb;

		if ( ! ( $wpdb instanceof \WP_PgSQL_Database\Database\WP_PgSQL_Db ) ) {
			return;
		}

		$query_count = is_array( $wpdb->queries ) ? count( $wpdb->queries ) : 0;

		$wp_admin_bar->add_node(
			[
				'id'    => 'wp-pgsql-database',
				'title' => sprintf(
					/* translators: %d: number of PostgreSQL queries */
					'PgSQL: %d %s',
					$query_count,
					_n( 'query', 'queries', $query_count, 'wp-pgsql-database' )
				),
				'href'  => admin_url( 'tools.php?page=wp-pgsql-database' ),
				'meta'  => [ 'title' => __( 'PostgreSQL Database Driver', 'wp-pgsql-database' ) ],
			]
		);
	}

	/**
	 * AJAX handler: return the current request query log as JSON.
	 *
	 * @return void
	 */
	public function ajax_query_log(): void {
		check_ajax_referer( 'wp_pgsql_database_admin' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'wp-pgsql-database' ) ], 403 );
		}

		$logger = WP_PgSQL_Query_Logger::get_instance();
		$log    = $logger->get_log();

		wp_send_json_success(
			[
				'count'   => count( $log ),
				'entries' => array_map(
					fn( array $entry ) => [
						'sql'        => $entry['sql'],
						'translated' => $entry['translated'],
						'duration'   => round( $entry['duration'] * 1000, 3 ),
						'caller'     => $entry['caller'],
					],
					$log
				),
			]
		);
	}

	/**
	 * Prevent cloning.
	 *
	 * @return void
	 */
	private function __clone(): void {}
}
