<?php
/**
 * PostgreSQL-aware wpdb subclass.
 *
 * @package WP_PgSQL_Database\Database
 * @since   1.0.0
 */

declare( strict_types=1 );

namespace WP_PgSQL_Database\Database;

use wpdb;
use WP_PgSQL_Database\Driver\WP_PgSQL_Driver;
use WP_PgSQL_Database\Translator\WP_PgSQL_Lexer;
use WP_PgSQL_Database\Translator\WP_PgSQL_Translator;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WP_PgSQL_Db
 *
 * Extends wpdb to route all queries through the PostgreSQL driver
 * and translation pipeline. WordPress core and plugins interact with this
 * class using the standard wpdb API; translation is fully transparent.
 */
class WP_PgSQL_Db extends wpdb {

	/**
	 * PostgreSQL driver instance.
	 *
	 * @var WP_PgSQL_Driver
	 */
	private WP_PgSQL_Driver $pg_driver;

	/**
	 * Query translator instance.
	 *
	 * @var WP_PgSQL_Translator
	 */
	private WP_PgSQL_Translator $translator;

	/**
	 * Whether the PostgreSQL connection is active.
	 *
	 * @var bool
	 */
	private bool $pg_connected = false;

	/**
	 * Sequence name cache: table → primary key column name.
	 *
	 * @var array<string, string>
	 */
	private array $sequence_cache = [];

	/**
	 * Constructor — initialise driver and translation pipeline.
	 *
	 * @param string $dbuser     Database username.
	 * @param string $dbpassword Database password.
	 * @param string $dbname     Database name.
	 * @param string $dbhost     Database host (optionally with port).
	 */
	public function __construct( string $dbuser, string $dbpassword, string $dbname, string $dbhost ) {
		$this->pg_driver  = new WP_PgSQL_Driver();
		$this->translator = new WP_PgSQL_Translator( new WP_PgSQL_Lexer() );

		$this->pg_connected = $this->pg_driver->connect( $dbhost, $dbuser, $dbpassword, $dbname );

		if ( ! $this->pg_connected ) {
			$this->bail(
				sprintf(
					/* translators: %s: error message */
					__( 'Failed to connect to PostgreSQL: %s', 'wp-pgsql-database' ),
					$this->pg_driver->last_error()
				),
				'db_connect_fail'
			);
		}

		// Initialise wpdb internals without calling its constructor's mysqli
		// connection logic. We set the required properties directly.
		$this->dbuser     = $dbuser;
		$this->dbpassword = $dbpassword;
		$this->dbname     = $dbname;
		$this->dbhost     = $dbhost;
		$this->ready      = $this->pg_connected;
	}

	/**
	 * Perform a database query.
	 *
	 * Overrides wpdb::query() to route through the translation pipeline
	 * and the PostgreSQL PDO driver.
	 *
	 * @param string $query MySQL SQL query.
	 * @return int|bool Number of rows affected/selected, or false on error.
	 */
	public function query( $query ): int|bool {
		if ( ! $this->ready ) {
			$this->check_current_query = true;
			return false;
		}

		// Allow plugins to filter the query (same as core wpdb).
		$query = apply_filters( 'query', $query );

		if ( ! $query ) {
			$this->insert_id = 0;
			return false;
		}

		$this->flush();
		$this->func_call = "\$db->query(\"{$query}\")";
		$this->last_query = $query;

		// Translate MySQL → PostgreSQL.
		$translated = $this->translator->translate( $query );

		// Execute via driver.
		$result = $this->pg_driver->query( $translated );

		if ( false === $result ) {
			$this->last_error = $this->pg_driver->last_error();

			if ( $this->show_errors ) {
				$this->print_error();
			}

			return false;
		}

		// Determine result type.
		$query_upper = strtoupper( ltrim( $query ) );

		if ( str_starts_with( $query_upper, 'SELECT' )
			|| str_starts_with( $query_upper, 'SHOW' )
			|| str_starts_with( $query_upper, 'DESCRIBE' )
			|| str_starts_with( $query_upper, 'EXPLAIN' )
		) {
			$this->last_result = $this->pg_driver->get_results();
			$this->num_rows    = count( $this->last_result );
			return $this->num_rows;
		}

		// INSERT — capture last insert ID.
		if ( str_starts_with( $query_upper, 'INSERT' ) ) {
			$this->insert_id   = $this->resolve_insert_id( $translated );
			$this->rows_affected = $this->pg_driver->affected_rows();
			return $this->rows_affected;
		}

		$this->rows_affected = $this->pg_driver->affected_rows();
		return $this->rows_affected;
	}

	/**
	 * Resolve the last insert ID for a PostgreSQL INSERT.
	 *
	 * @param string $translated Translated INSERT SQL.
	 * @return int
	 */
	private function resolve_insert_id( string $translated ): int {
		// Extract table name from INSERT INTO "table".
		if ( preg_match( '/INSERT\s+INTO\s+"?(\w+)"?/i', $translated, $m ) ) {
			$table    = $m[1];
			$sequence = $this->get_sequence_name( $table );
			return (int) $this->pg_driver->insert_id( $sequence );
		}

		return (int) $this->pg_driver->insert_id();
	}

	/**
	 * Look up or cache the primary key sequence name for a table.
	 *
	 * @param string $table Unquoted table name.
	 * @return string Sequence name, e.g. "wp_posts_ID_seq".
	 */
	private function get_sequence_name( string $table ): string {
		if ( isset( $this->sequence_cache[ $table ] ) ) {
			return $this->sequence_cache[ $table ];
		}

		$sql    = "SELECT pg_get_serial_sequence('{$table}', column_name) AS seq
		           FROM information_schema.columns
		           WHERE table_name = '{$table}'
		             AND column_default LIKE 'nextval%'
		           LIMIT 1";
		$result = $this->pg_driver->query( $sql );
		$rows   = $result ? $this->pg_driver->get_results() : [];

		$seq = ! empty( $rows[0]->seq ) ? (string) $rows[0]->seq : '';

		$this->sequence_cache[ $table ] = $seq;
		return $seq;
	}

	/**
	 * Escape a string for safe query inclusion.
	 *
	 * @param string $string Value to escape.
	 * @return string
	 */
	public function _real_escape( $string ): string {
		return $this->pg_driver->escape_string( (string) $string );
	}

	/**
	 * Select a database — no-op for PostgreSQL (schema is fixed at connect time).
	 *
	 * @param string $db  Database name.
	 * @param wpdb   $wpdb wpdb instance (unused).
	 * @return bool
	 */
	public function select( $db, $wpdb = null ): bool {
		// Database selection is handled at connection time via DSN.
		return true;
	}

	/**
	 * Flush cached query results.
	 *
	 * @return void
	 */
	public function flush(): void {
		$this->last_result  = [];
		$this->col_info     = null;
		$this->last_query   = null;
		$this->rows_affected = 0;
		$this->num_rows     = 0;
		$this->last_error   = '';
	}

	/**
	 * Check whether the database connection is alive.
	 *
	 * @return bool
	 */
	public function check_connection( $allow_bail = true ): bool {
		return $this->pg_driver->is_connected();
	}

	/**
	 * Return the PostgreSQL server version string.
	 *
	 * @return string
	 */
	public function db_version(): string {
		return $this->pg_driver->get_server_version();
	}

	/**
	 * Close the database connection.
	 *
	 * @return bool
	 */
	public function close(): bool {
		$this->pg_driver->close();
		return true;
	}
}
