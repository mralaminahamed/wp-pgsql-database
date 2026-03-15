<?php
/**
 * MySQL → PostgreSQL schema type mapper.
 *
 * @package WP_PgSQL_Database\Schema
 * @since   1.0.0
 */

declare( strict_types=1 );

namespace WP_PgSQL_Database\Schema;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WP_PgSQL_Schema_Mapper
 *
 * Rewrites MySQL CREATE TABLE and ALTER TABLE DDL statements to
 * PostgreSQL-compatible equivalents. Handles column type mapping,
 * AUTO_INCREMENT → SERIAL, charset/collation stripping, and
 * ENGINE= clause removal.
 */
class WP_PgSQL_Schema_Mapper {

	/**
	 * MySQL → PostgreSQL column type map.
	 *
	 * Keys are MySQL type patterns (may include precision placeholder %s).
	 *
	 * @var array<string, string>
	 */
	private const TYPE_MAP = [
		// Integer types.
		'TINYINT(1)'   => 'BOOLEAN',
		'TINYINT'      => 'SMALLINT',
		'SMALLINT'     => 'SMALLINT',
		'MEDIUMINT'    => 'INTEGER',
		'INT'          => 'INTEGER',
		'INTEGER'      => 'INTEGER',
		'BIGINT'       => 'BIGINT',

		// Floating point.
		'FLOAT'        => 'REAL',
		'DOUBLE'       => 'DOUBLE PRECISION',
		'DECIMAL'      => 'DECIMAL',
		'NUMERIC'      => 'NUMERIC',

		// String types.
		'CHAR'         => 'CHAR',
		'VARCHAR'      => 'VARCHAR',
		'TINYTEXT'     => 'TEXT',
		'TEXT'         => 'TEXT',
		'MEDIUMTEXT'   => 'TEXT',
		'LONGTEXT'     => 'TEXT',

		// Binary / blob types (mapped to bytea or text).
		'BINARY'       => 'BYTEA',
		'VARBINARY'    => 'BYTEA',
		'TINYBLOB'     => 'BYTEA',
		'BLOB'         => 'BYTEA',
		'MEDIUMBLOB'   => 'BYTEA',
		'LONGBLOB'     => 'BYTEA',

		// Date/time types.
		'DATE'         => 'DATE',
		'TIME'         => 'TIME',
		'DATETIME'     => 'TIMESTAMP',
		'TIMESTAMP'    => 'TIMESTAMP',
		'YEAR'         => 'SMALLINT',

		// JSON.
		'JSON'         => 'JSONB',

		// Enum / set (flattened to TEXT + constraint).
		'ENUM'         => 'TEXT',
		'SET'          => 'TEXT',
	];

	/**
	 * Patterns stripped wholesale from MySQL DDL.
	 *
	 * @var string[]
	 */
	private const STRIP_PATTERNS = [
		'/\s+UNSIGNED/i',
		'/\s+ZEROFILL/i',
		'/\s+CHARACTER\s+SET\s+\w+/i',
		'/\s+COLLATE\s+[\w_]+/i',
		'/\s+ENGINE\s*=\s*\w+/i',
		'/\s+DEFAULT\s+CHARSET\s*=\s*[\w_]+/i',
		'/\s+COLLATE\s*=\s*[\w_]+/i',
		'/\s+ROW_FORMAT\s*=\s*\w+/i',
		'/\s+AUTO_INCREMENT\s*=\s*\d+/i',
		'/\s+COMMENT\s*=\s*\'[^\']*\'/i',
		'/\s+KEY_BLOCK_SIZE\s*=\s*\d+/i',
	];

	/**
	 * Rewrite a MySQL DDL statement for PostgreSQL.
	 *
	 * @param string $sql MySQL DDL SQL.
	 * @return string PostgreSQL-compatible DDL.
	 */
	public function rewrite( string $sql ): string {
		// Remap column types.
		$sql = $this->remap_types( $sql );

		// AUTO_INCREMENT → SERIAL on column definitions.
		$sql = $this->rewrite_auto_increment( $sql );

		// Strip MySQL-specific clauses.
		foreach ( self::STRIP_PATTERNS as $pattern ) {
			$sql = preg_replace( $pattern, '', $sql ) ?? $sql;
		}

		// Rewrite fulltext / spatial indexes (PostgreSQL handles these differently).
		$sql = $this->rewrite_indexes( $sql );

		return $sql;
	}

	/**
	 * Apply the type mapping to all column definitions in a CREATE TABLE statement.
	 *
	 * @param string $sql MySQL DDL.
	 * @return string Rewritten DDL.
	 */
	private function remap_types( string $sql ): string {
		foreach ( self::TYPE_MAP as $mysql_type => $pgsql_type ) {
			// Build a regex that matches the type (with optional precision/scale).
			$escaped = preg_quote( $mysql_type, '/' );

			if ( in_array( $mysql_type, [ 'TINYINT(1)', 'JSON' ], true ) ) {
				// Exact match for parameterised types.
				$sql = preg_replace( "/\b{$escaped}\b/i", $pgsql_type, $sql ) ?? $sql;
			} else {
				// Match type followed optionally by (precision) or (precision, scale).
				$sql = preg_replace( "/\b{$escaped}(\s*\(\s*\d+\s*(?:,\s*\d+\s*)?\))?/i", $pgsql_type . '$1', $sql ) ?? $sql;
			}
		}

		return $sql;
	}

	/**
	 * Convert AUTO_INCREMENT column definitions to SERIAL or BIGSERIAL.
	 *
	 * @param string $sql MySQL DDL.
	 * @return string Rewritten DDL.
	 */
	private function rewrite_auto_increment( string $sql ): string {
		// BIGINT ... AUTO_INCREMENT → BIGSERIAL
		$sql = preg_replace(
			'/\bBIGINT\b([^,]*)\bAUTO_INCREMENT\b/i',
			'BIGSERIAL',
			$sql
		) ?? $sql;

		// INT/INTEGER ... AUTO_INCREMENT → SERIAL
		$sql = preg_replace(
			'/\b(?:INT|INTEGER|MEDIUMINT|SMALLINT|TINYINT)\b([^,]*)\bAUTO_INCREMENT\b/i',
			'SERIAL',
			$sql
		) ?? $sql;

		// Catch any remaining AUTO_INCREMENT.
		$sql = preg_replace( '/\bAUTO_INCREMENT\b/i', '', $sql ) ?? $sql;

		return $sql;
	}

	/**
	 * Rewrite MySQL-specific index types to PostgreSQL equivalents.
	 *
	 * @param string $sql MySQL DDL.
	 * @return string Rewritten DDL.
	 */
	private function rewrite_indexes( string $sql ): string {
		// FULLTEXT INDEX → drop (PostgreSQL uses tsvector; we cannot inline it here).
		$sql = preg_replace( '/,\s*FULLTEXT\s+(?:KEY|INDEX)\s+\w+\s*\([^)]+\)/i', '', $sql ) ?? $sql;

		// SPATIAL INDEX → drop (requires PostGIS).
		$sql = preg_replace( '/,\s*SPATIAL\s+(?:KEY|INDEX)\s+\w+\s*\([^)]+\)/i', '', $sql ) ?? $sql;

		// KEY `name` (col) → INDEX name ON table (col) is handled separately at the
		// CREATE TABLE level — for now we convert inline KEY clauses to nothing,
		// allowing the Installer to issue separate CREATE INDEX statements.
		$sql = preg_replace( '/,\s*(?:INDEX|KEY)\s+`?\w+`?\s*\([^)]+\)/i', '', $sql ) ?? $sql;

		return $sql;
	}
}
