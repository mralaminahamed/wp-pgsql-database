<?php
/**
 * Database migrator — handles schema upgrades across plugin versions.
 *
 * @package WP_PgSQL_Database\Migration
 * @since   1.0.0
 */

declare( strict_types=1 );

namespace WP_PgSQL_Database\Migration;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WP_PgSQL_Migrator
 *
 * Applies ordered, versioned migration steps when the stored plugin
 * version is older than the current version. Each migration step is
 * a private method named migrate_to_{version} (e.g. migrate_to_1_1_0).
 */
class WP_PgSQL_Migrator {

	/**
	 * Option key storing the last-applied migration version.
	 *
	 * @var string
	 */
	private const VERSION_OPTION = 'wp_pgsql_db_schema_version';

	/**
	 * Run any pending migrations.
	 *
	 * @return void
	 */
	public function run(): void {
		$current  = (string) get_option( self::VERSION_OPTION, '0.0.0' );
		$target   = WP_PGSQL_DB_VERSION;

		if ( version_compare( $current, $target, '>=' ) ) {
			return;
		}

		$migrations = $this->get_pending_migrations( $current );

		foreach ( $migrations as $version => $method ) {
			if ( method_exists( $this, $method ) ) {
				$this->{$method}();
				update_option( self::VERSION_OPTION, $version, false );
			}
		}

		// Ensure final version is recorded.
		update_option( self::VERSION_OPTION, $target, false );
	}

	/**
	 * Return a sorted list of migration methods pending since $from_version.
	 *
	 * @param string $from_version Currently applied version.
	 * @return array<string, string> Map of version => method name.
	 */
	private function get_pending_migrations( string $from_version ): array {
		$all = [
			'1.0.0' => 'migrate_to_1_0_0',
		];

		return array_filter(
			$all,
			fn( string $v ) => version_compare( $v, $from_version, '>' ),
			ARRAY_FILTER_USE_KEY
		);
	}

	/**
	 * Migration to 1.0.0 — initial schema setup.
	 *
	 * @return void
	 */
	private function migrate_to_1_0_0(): void {
		global $wpdb;

		// Create the plugin's own logging table if WP_DEBUG is enabled.
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		$table = $wpdb->prefix . 'pgsql_query_log';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query(
			"CREATE TABLE IF NOT EXISTS {$table} (
				id         BIGSERIAL PRIMARY KEY,
				query_hash VARCHAR(64)  NOT NULL,
				query_sql  TEXT         NOT NULL,
				duration   NUMERIC(12,6) NOT NULL DEFAULT 0,
				created_at TIMESTAMP    NOT NULL DEFAULT NOW()
			)"
		);

		// Index on created_at for log pruning.
		$wpdb->query( "CREATE INDEX IF NOT EXISTS idx_pgsql_log_created ON {$table} (created_at)" );
	}
}
