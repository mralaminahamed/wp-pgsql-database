<?php
/**
 * Admin settings page.
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
 * Class WP_PgSQL_Admin
 *
 * Registers and renders the plugin's Tools › PostgreSQL Database admin page.
 */
class WP_PgSQL_Admin {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Admin page hook suffix.
	 *
	 * @var string
	 */
	private string $page_hook = '';

	/**
	 * Private constructor.
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_post_wp_pgsql_install_dropin', array( $this, 'handle_install_dropin' ) );
		add_action( 'admin_post_wp_pgsql_remove_dropin', array( $this, 'handle_remove_dropin' ) );
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
	 * Register the admin menu entry under Tools.
	 *
	 * @return void
	 */
	public function register_menu(): void {
		$this->page_hook = add_management_page(
			__( 'PostgreSQL Database', 'wp-pgsql-database' ),
			__( 'PostgreSQL DB', 'wp-pgsql-database' ),
			'manage_options',
			'wp-pgsql-database',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Render the admin settings page.
	 *
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wp-pgsql-database' ) );
		}

		$dropin_active  = WP_PgSQL_Installer::is_dropin_active();
		$dropin_current = WP_PgSQL_Installer::is_dropin_current();
		$engine_set     = defined( 'DB_ENGINE' ) && 'pgsql' === DB_ENGINE;
		$plugin_version = WP_PGSQL_DB_VERSION;
		$form_action    = admin_url( 'admin-post.php' );

		$notices = $this->render_notices_html();

		include WP_PGSQL_DB_PATH . 'templates/admin/page-settings.php';
	}

	/**
	 * Render admin notice for query-string status messages.
	 *
	 * @return string
	 */
	private function render_notices_html(): string {
		if ( empty( $_GET['wp_pgsql_notice'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return '';
		}

		$notice = sanitize_key( $_GET['wp_pgsql_notice'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$map    = array(
			'dropin_installed' => array( 'success', __( 'Drop-in installed successfully.', 'wp-pgsql-database' ) ),
			'dropin_removed'   => array( 'success', __( 'Drop-in removed.', 'wp-pgsql-database' ) ),
			'dropin_failed'    => array(
				'error',
				__( 'Drop-in installation failed. Please check file permissions.', 'wp-pgsql-database' ),
			),
		);

		if ( ! isset( $map[ $notice ] ) ) {
			return '';
		}

		[ $type, $message ] = $map[ $notice ];

		return sprintf(
			'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
			esc_attr( $type ),
			esc_html( $message )
		);
	}

	/**
	 * Handle drop-in installation form submission.
	 *
	 * @return void
	 */
	public function handle_install_dropin(): void {
		check_admin_referer( 'wp_pgsql_install_dropin' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'wp-pgsql-database' ) );
		}

		$result = WP_PgSQL_Installer::install_dropin();
		$notice = $result ? 'dropin_installed' : 'dropin_failed';

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'            => 'wp-pgsql-database',
					'wp_pgsql_notice' => $notice,
				),
				admin_url( 'tools.php' )
			)
		);
		exit;
	}

	/**
	 * Handle drop-in removal form submission.
	 *
	 * @return void
	 */
	public function handle_remove_dropin(): void {
		check_admin_referer( 'wp_pgsql_remove_dropin' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'wp-pgsql-database' ) );
		}

		WP_PgSQL_Installer::remove_dropin();

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'            => 'wp-pgsql-database',
					'wp_pgsql_notice' => 'dropin_removed',
				),
				admin_url( 'tools.php' )
			)
		);
		exit;
	}

	/**
	 * Prevent cloning.
	 *
	 * @return void
	 */
	private function __clone() {
	}
}
