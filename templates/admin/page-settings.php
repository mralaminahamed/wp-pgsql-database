<?php
/**
 * Admin settings page template.
 *
 * @package WP_PgSQL_Database
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap wp-pgsql-admin">
	<h1><?php esc_html_e( 'PostgreSQL Database Driver', 'wp-pgsql-database' ); ?></h1>

	<?php // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php echo $notices; ?>
	<?php // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped ?>

	<div class="wp-pgsql-status-cards">

		<!-- Drop-in status -->
		<div class="wp-pgsql-card">
			<h2><?php esc_html_e( 'Drop-in Status', 'wp-pgsql-database' ); ?></h2>
			<table class="widefat striped">
				<tbody>
				<tr>
					<td><?php esc_html_e( 'db.php installed', 'wp-pgsql-database' ); ?></td>
					<td>
						<?php if ( $dropin_active ) : ?>
							<span class="wp-pgsql-badge success"><?php esc_html_e( 'Active', 'wp-pgsql-database' ); ?></span>
						<?php else : ?>
							<span class="wp-pgsql-badge error"><?php esc_html_e( 'Not installed', 'wp-pgsql-database' ); ?></span>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Drop-in version', 'wp-pgsql-database' ); ?></td>
					<td>
						<?php if ( $dropin_current ) : ?>
							<span class="wp-pgsql-badge success"><?php esc_html_e( 'Up to date', 'wp-pgsql-database' ); ?></span>
						<?php else : ?>
							<span class="wp-pgsql-badge warning"><?php esc_html_e( 'Update available', 'wp-pgsql-database' ); ?></span>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'DB_ENGINE constant', 'wp-pgsql-database' ); ?></td>
					<td>
						<?php if ( $engine_set ) : ?>
							<span class="wp-pgsql-badge success"><?php esc_html_e( 'pgsql', 'wp-pgsql-database' ); ?></span>
						<?php else : ?>
							<span class="wp-pgsql-badge warning"><?php esc_html_e( 'Not defined', 'wp-pgsql-database' ); ?></span>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Plugin version', 'wp-pgsql-database' ); ?></td>
					<td><?php echo esc_html( $plugin_version ); ?></td>
				</tr>
				</tbody>
			</table>

			<div class="wp-pgsql-actions">
				<?php if ( ! $dropin_active || ! $dropin_current ) : ?>
					<form method="post" action="<?php echo esc_url( $form_action ); ?>">
						<?php wp_nonce_field( 'wp_pgsql_install_dropin' ); ?>
						<input type="hidden" name="action" value="wp_pgsql_install_dropin">
						<button type="submit" class="button button-primary">
							<?php echo $dropin_active ? esc_html__( 'Update Drop-in', 'wp-pgsql-database' ) : esc_html__( 'Install Drop-in', 'wp-pgsql-database' ); ?>
						</button>
					</form>
				<?php endif; ?>

				<?php if ( $dropin_active ) : ?>
					<form method="post" action="<?php echo esc_url( $form_action ); ?>">
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
