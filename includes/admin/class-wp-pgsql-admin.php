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
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'admin_post_wp_pgsql_install_dropin', [ $this, 'handle_install_dropin' ] );
		add_action( 'admin_post_wp_pgsql_remove_dropin', [ $this, 'handle_remove_dropin' ] );
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
			[ $this, 'render_page' ]
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

		?>
		<div class="wrap wp-pgsql-admin">
			<h1><?php esc_html_e( 'PostgreSQL Database Driver', 'wp-pgsql-database' ); ?></h1>

			<?php $this->render_notices(); ?>

			<div class="wp-pgsql-status-cards">

				<!-- Drop-in status -->
				<div class="wp-pgsql-card">
					<h2><?php esc_html_e( 'Drop-in Status', 'wp-pgsql-database' ); ?></h2>
					<table class="widefat striped">
						<tbody>
							<tr>
								<td><?php esc_html_e( 'db.php installed', 'wp-pgsql-database' ); ?></td>
								<td><?php echo $dropin_active ? '<span class="wp-pgsql-badge success">' . esc_html__( 'Active', 'wp-pgsql-database' ) . '</span>' : '<span class="wp-pgsql-badge error">' . esc_html__( 'Not installed', 'wp-pgsql-database' ) . '</span>'; ?></td>
							</tr>
							<tr>
								<td><?php esc_html_e( 'Drop-in version', 'wp-pgsql-database' ); ?></td>
								<td><?php echo $dropin_current ? '<span class="wp-pgsql-badge success">' . esc_html__( 'Up to date', 'wp-pgsql-database' ) . '</span>' : '<span class="wp-pgsql-badge warning">' . esc_html__( 'Update available', 'wp-pgsql-database' ) . '</span>'; ?></td>
							</tr>
							<tr>
								<td><?php esc_html_e( 'DB_ENGINE constant', 'wp-pgsql-database' ); ?></td>
								<td><?php echo $engine_set ? '<span class="wp-pgsql-badge success">' . esc_html__( 'pgsql', 'wp-pgsql-database' ) . '</span>' : '<span class="wp-pgsql-badge warning">' . esc_html__( 'Not defined', 'wp-pgsql-database' ) . '</span>'; ?></td>
							</tr>
							<tr>
								<td><?php esc_html_e( 'Plugin version', 'wp-pgsql-database' ); ?></td>
								<td><?php echo esc_html( WP_PGSQL_DB_VERSION ); ?></td>
							</tr>
						</tbody>
					</table>

					<div class="wp-pgsql-actions">
						<?php if ( ! $dropin_active || ! $dropin_current ) : ?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<?php wp_nonce_field( 'wp_pgsql_install_dropin' ); ?>
							<input type="hidden" name="action" value="wp_pgsql_install_dropin">
							<button type="submit" class="button button-primary">
								<?php echo $dropin_active ? esc_html__( 'Update Drop-in', 'wp-pgsql-database' ) : esc_html__( 'Install Drop-in', 'wp-pgsql-database' ); ?>
							</button>
						</form>
						<?php endif; ?>

						<?php if ( $dropin_active ) : ?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<?php wp_nonce_field( 'wp_pgsql_remove_dropin' ); ?>
							<input type="hidden" name="action" value="wp_pgsql_remove_dropin">
							<button type="submit" class="button button-secondary">
								<?php esc_html_e( 'Remove Drop-in', 'wp-pgsql-database' ); ?>
							</button>
						</form>
						<?php endif; ?>
					</div>
				</div>

				<!-- wp-config.php instructions -->
				<div class="wp-pgsql-card">
					<h2><?php esc_html_e( 'Configuration', 'wp-pgsql-database' ); ?></h2>
					<p><?php esc_html_e( 'Add the following constant to your wp-config.php to activate the PostgreSQL driver:', 'wp-pgsql-database' ); ?></p>
					<pre><code>define( 'DB_ENGINE', 'pgsql' );
define( 'DB_HOST',     'localhost' );
define( 'DB_NAME',     'your_db' );
define( 'DB_USER',     'your_user' );
define( 'DB_PASSWORD', 'your_password' );</code></pre>
					<p class="description">
						<?php esc_html_e( 'The DB_HOST value may include a custom port, e.g. "localhost:5432".', 'wp-pgsql-database' ); ?>
					</p>
				</div>

			</div>
		</div>
		<?php
	}

	/**
	 * Render admin notice for query-string status messages.
	 *
	 * @return void
	 */
	private function render_notices(): void {
		if ( empty( $_GET['wp_pgsql_notice'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$notice = sanitize_key( $_GET['wp_pgsql_notice'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$map    = [
			'dropin_installed' => [ 'success', __( 'Drop-in installed successfully.', 'wp-pgsql-database' ) ],
			'dropin_removed'   => [ 'success', __( 'Drop-in removed.', 'wp-pgsql-database' ) ],
			'dropin_failed'    => [ 'error',   __( 'Drop-in installation failed. Please check file permissions.', 'wp-pgsql-database' ) ],
		];

		if ( ! isset( $map[ $notice ] ) ) {
			return;
		}

		[ $type, $message ] = $map[ $notice ];

		printf(
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
				[ 'page' => 'wp-pgsql-database', 'wp_pgsql_notice' => $notice ],
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
				[ 'page' => 'wp-pgsql-database', 'wp_pgsql_notice' => 'dropin_removed' ],
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
	private function __clone(): void {}
}
