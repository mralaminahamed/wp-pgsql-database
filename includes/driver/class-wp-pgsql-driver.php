<?php
/**
 * PostgreSQL PDO driver.
 *
 * @package WP_PgSQL_Database\Driver
 * @since   1.0.0
 */

declare( strict_types=1 );

namespace WP_PgSQL_Database\Driver;

use PDO;
use PDOException;
use PDOStatement;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WP_PgSQL_Driver
 *
 * Wraps PDO with the pgsql driver. Implements WP_PgSQL_Driver_Interface
 * so it can be swapped for any alternative driver without touching the
 * translation pipeline or the wpdb subclass.
 */
class WP_PgSQL_Driver implements WP_PgSQL_Driver_Interface {

	/**
	 * Active PDO connection.
	 *
	 * @var PDO|null
	 */
	private ?PDO $pdo = null;

	/**
	 * Last executed PDO statement.
	 *
	 * @var PDOStatement|null
	 */
	private ?PDOStatement $last_statement = null;

	/**
	 * Last error message.
	 *
	 * @var string
	 */
	private string $last_error = '';

	/**
	 * Number of rows affected by the last modifying query.
	 *
	 * @var int
	 */
	private int $affected_rows = 0;

	/**
	 * {@inheritDoc}
	 */
	public function connect( string $host, string $user, string $password, string $db_name ): bool {
		// Parse optional port from host string (e.g. "localhost:5432").
		$port = '5432';
		if ( str_contains( $host, ':' ) ) {
			[ $host, $port ] = explode( ':', $host, 2 );
		}

		$dsn = sprintf(
			'pgsql:host=%s;port=%s;dbname=%s',
			$host,
			$port,
			$db_name
		);

		try {
			$this->pdo = new PDO(
				$dsn,
				$user,
				$password,
				[
					PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
					PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
					PDO::ATTR_EMULATE_PREPARES   => false,
					PDO::ATTR_STRINGIFY_FETCHES  => false,
				]
			);

			// Set session timezone to UTC to match WordPress expectations.
			$this->pdo->exec( "SET TIME ZONE 'UTC'" );

			return true;
		} catch ( PDOException $e ) {
			$this->last_error = $e->getMessage();
			return false;
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function query( string $sql ): mixed {
		if ( null === $this->pdo ) {
			$this->last_error = 'No active database connection.';
			return false;
		}

		$this->last_error     = '';
		$this->last_statement = null;
		$this->affected_rows  = 0;

		try {
			$statement = $this->pdo->query( $sql );

			if ( false === $statement ) {
				return false;
			}

			$this->last_statement = $statement;
			$this->affected_rows  = $statement->rowCount();

			return $statement;
		} catch ( PDOException $e ) {
			$this->last_error = $e->getMessage();
			return false;
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function escape_string( string $value ): string {
		if ( null === $this->pdo ) {
			// Fallback: manually escape common hazards.
			return addslashes( $value );
		}

		// PDO::quote() wraps with quotes; strip them to match wpdb behaviour.
		$quoted = $this->pdo->quote( $value );

		return substr( $quoted, 1, -1 );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array<int, object>
	 */
	public function get_results(): array {
		if ( null === $this->last_statement ) {
			return [];
		}

		return $this->last_statement->fetchAll( PDO::FETCH_OBJ ) ?: [];
	}

	/**
	 * {@inheritDoc}
	 */
	public function affected_rows(): int {
		return $this->affected_rows;
	}

	/**
	 * {@inheritDoc}
	 */
	public function insert_id( string $sequence_name = '' ): int|string {
		if ( null === $this->pdo ) {
			return 0;
		}

		try {
			$id = $this->pdo->lastInsertId( $sequence_name ?: null );
			return is_numeric( $id ) ? (int) $id : $id;
		} catch ( PDOException $e ) {
			$this->last_error = $e->getMessage();
			return 0;
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function last_error(): string {
		return $this->last_error;
	}

	/**
	 * {@inheritDoc}
	 */
	public function close(): void {
		$this->pdo            = null;
		$this->last_statement = null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function is_connected(): bool {
		return null !== $this->pdo;
	}

	/**
	 * {@inheritDoc}
	 */
	public function begin_transaction(): bool {
		if ( null === $this->pdo ) {
			return false;
		}

		try {
			return $this->pdo->beginTransaction();
		} catch ( PDOException $e ) {
			$this->last_error = $e->getMessage();
			return false;
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function commit(): bool {
		if ( null === $this->pdo ) {
			return false;
		}

		try {
			return $this->pdo->commit();
		} catch ( PDOException $e ) {
			$this->last_error = $e->getMessage();
			return false;
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function rollback(): bool {
		if ( null === $this->pdo ) {
			return false;
		}

		try {
			return $this->pdo->rollBack();
		} catch ( PDOException $e ) {
			$this->last_error = $e->getMessage();
			return false;
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_server_version(): string {
		if ( null === $this->pdo ) {
			return '';
		}

		try {
			$stmt    = $this->pdo->query( 'SELECT version()' );
			$row     = $stmt ? $stmt->fetch( PDO::FETCH_NUM ) : false;
			return $row ? (string) $row[0] : '';
		} catch ( PDOException $e ) {
			return '';
		}
	}
}
