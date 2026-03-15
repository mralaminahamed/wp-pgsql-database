<?php
/**
 * Database driver interface.
 *
 * @package WP_PgSQL_Database\Driver
 * @since   1.0.0
 */

declare( strict_types=1 );

namespace WP_PgSQL_Database\Driver;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface WP_PgSQL_Driver_Interface
 *
 * Defines the contract every database driver must fulfil.
 * Implementing a new driver (e.g. SQLite, MariaDB) requires only
 * satisfying this interface — no changes to the translation pipeline.
 */
interface WP_PgSQL_Driver_Interface {

	/**
	 * Open a connection to the target database.
	 *
	 * @param string $host     Database host (including optional port, e.g. "localhost:5432").
	 * @param string $user     Database username.
	 * @param string $password Database password.
	 * @param string $db_name  Database (schema) name.
	 * @return bool True on success, false on failure.
	 */
	public function connect( string $host, string $user, string $password, string $db_name ): bool;

	/**
	 * Execute a translated SQL query.
	 *
	 * @param string $sql Translated, driver-specific SQL string.
	 * @return mixed Result resource, row count, or false on error.
	 */
	public function query( string $sql ): mixed;

	/**
	 * Escape a string value for safe inclusion in a query.
	 *
	 * @param string $value Raw value to escape.
	 * @return string Escaped value (without surrounding quotes).
	 */
	public function escape_string( string $value ): string;

	/**
	 * Return all rows from the last executed query as an array of objects.
	 *
	 * @return array<int, object>
	 */
	public function get_results(): array;

	/**
	 * Return the number of rows affected by the last INSERT/UPDATE/DELETE.
	 *
	 * @return int
	 */
	public function affected_rows(): int;

	/**
	 * Return the auto-generated ID from the last INSERT statement.
	 *
	 * @param string $sequence_name PostgreSQL sequence name (required for PgSQL).
	 * @return int|string
	 */
	public function insert_id( string $sequence_name = '' ): int|string;

	/**
	 * Return the last error message from the driver.
	 *
	 * @return string Empty string if no error.
	 */
	public function last_error(): string;

	/**
	 * Close the database connection.
	 *
	 * @return void
	 */
	public function close(): void;

	/**
	 * Check whether a connection is currently open.
	 *
	 * @return bool
	 */
	public function is_connected(): bool;

	/**
	 * Begin a database transaction.
	 *
	 * @return bool
	 */
	public function begin_transaction(): bool;

	/**
	 * Commit the current transaction.
	 *
	 * @return bool
	 */
	public function commit(): bool;

	/**
	 * Roll back the current transaction.
	 *
	 * @return bool
	 */
	public function rollback(): bool;

	/**
	 * Return the server version string.
	 *
	 * @return string
	 */
	public function get_server_version(): string;
}
