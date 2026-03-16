<?php
/**
 * MySQL-to-PostgreSQL query translator.
 *
 * @package WP_PgSQL_Database\Translator
 * @since   1.0.0
 */

declare( strict_types=1 );

namespace WP_PgSQL_Database\Translator;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WP_PgSQL_Translator
 *
 * Rewrites MySQL-dialect SQL to PostgreSQL-compatible SQL.
 * Operates on the token stream produced by WP_PgSQL_Lexer.
 *
 * Translation targets (non-exhaustive):
 *  - Backtick identifiers  → double-quoted identifiers
 *  - AUTO_INCREMENT        → SERIAL / BIGSERIAL
 *  - UNSIGNED / ZEROFILL   → stripped
 *  - TINYINT(1)            → BOOLEAN
 *  - INSERT IGNORE         → INSERT … ON CONFLICT DO NOTHING
 *  - ON DUPLICATE KEY UPDATE → ON CONFLICT (…) DO UPDATE SET …
 *  - SHOW TABLES / SHOW COLUMNS → information_schema equivalents
 *  - LIMIT x,y offset syntax → LIMIT y OFFSET x
 *  - DATE_FORMAT / UNIX_TIMESTAMP / NOW / IFNULL / IF → PgSQL equivalents
 *  - REGEXP / RLIKE        → ~ operator
 *  - Backtick-quoted booleans (1/0 for TRUE/FALSE in TINYINT(1) columns)
 */
class WP_PgSQL_Translator {

	/**
	 * Lexer instance.
	 *
	 * @var WP_PgSQL_Lexer
	 */
	private WP_PgSQL_Lexer $lexer;

	/**
	 * Token stream being processed.
	 *
	 * @var WP_PgSQL_Token[]
	 */
	private array $tokens = array();

	/**
	 * Current token index.
	 *
	 * @var int
	 */
	private int $index = 0;

	/**
	 * WP_PgSQL_Translator constructor.
	 *
	 * @param WP_PgSQL_Lexer $lexer Injected lexer instance.
	 */
	public function __construct( WP_PgSQL_Lexer $lexer ) {
		$this->lexer = $lexer;
	}

	/**
	 * Translate a MySQL SQL string to PostgreSQL-compatible SQL.
	 *
	 * @param string $sql Raw MySQL SQL.
	 *
	 * @return string Translated PostgreSQL SQL.
	 */
	public function translate( string $sql ): string {
		$sql = trim( $sql );

		if ( '' === $sql ) {
			return $sql;
		}

		// Fast path: intercept SHOW statements before tokenising.
		if ( 0 === stripos( $sql, 'SHOW ' ) ) {
			$rewritten = $this->translate_show_statement( $sql );
			if ( null !== $rewritten ) {
				return $rewritten;
			}
		}

		$this->tokens = $this->lexer->tokenise( $sql );
		$this->index  = 0;

		return $this->rewrite_tokens();
	}

	/**
	 * Walk the token stream and emit translated SQL.
	 *
	 * @return string
	 */
	private function rewrite_tokens(): string {
		$output = '';

		while ( ! $this->is_eof() ) {
			$token = $this->current();

			switch ( $token->type ) {
				case WP_PgSQL_Token::TYPE_IDENTIFIER:
					// Backtick identifiers → double-quoted.
					if ( str_starts_with( $token->value, '`' ) ) {
						$name    = trim( $token->value, '`' );
						$output .= '"' . $name . '"';
					} else {
						$output .= $token->value;
					}
					$this->advance();
					break;

				case WP_PgSQL_Token::TYPE_KEYWORD:
					$output .= $this->rewrite_keyword();
					break;

				default:
					$output .= $token->value;
					$this->advance();
					break;
			}
		}

		return $output;
	}

	/**
	 * Rewrite a keyword token, potentially consuming additional tokens.
	 *
	 * @return string Translated fragment.
	 */
	private function rewrite_keyword(): string {
		$token = $this->current();
		$upper = $token->normalised;

		switch ( $upper ) {
			case 'INSERT':
				return $this->rewrite_insert();

			case 'AUTO_INCREMENT':
				// AUTO_INCREMENT is part of a column definition; replaced in schema mapper.
				// Here we drop it from DML contexts.
				$this->advance();

				return '';

			case 'UNSIGNED':
			case 'ZEROFILL':
				// PostgreSQL has no UNSIGNED; drop silently.
				$this->advance();

				return '';

			case 'REGEXP':
			case 'RLIKE':
				$this->advance();

				return '~';

			case 'IFNULL':
				$this->advance();

				return 'COALESCE';

			case 'ISNULL':
				$this->advance();

				return '( IS NULL )';

			case 'LIMIT':
				return $this->rewrite_limit();

			case 'DESCRIBE':
				return $this->rewrite_describe();

			default:
				$this->advance();

				return $token->value;
		}
	}

	/**
	 * Rewrite INSERT [IGNORE] … [ON DUPLICATE KEY UPDATE …].
	 *
	 * @return string
	 */
	private function rewrite_insert(): string {
		$this->advance(); // consume INSERT
		$ignore   = false;
		$fragment = 'INSERT ';

		// Consume optional LOW_PRIORITY / DELAYED / HIGH_PRIORITY.
		while ( ! $this->is_eof() && $this->current()->is_keyword( 'LOW_PRIORITY', 'DELAYED', 'HIGH_PRIORITY' ) ) {
			$this->advance();
		}

		$this->skip_whitespace();

		// Detect IGNORE.
		if ( ! $this->is_eof() && $this->current()->is_keyword( 'IGNORE' ) ) {
			$ignore = true;
			$this->advance();
		}

		// Collect everything up to ON DUPLICATE KEY or EOF.
		$body            = '';
		$conflict_update = '';

		while ( ! $this->is_eof() ) {
			$tok = $this->current();

			if ( $tok->is_keyword( 'ON' ) && $this->peek_significant( 1 )->is_keyword( 'DUPLICATE' ) ) {
				// Skip ON DUPLICATE KEY UPDATE.
				$this->advance(); // ON
				$this->skip_keyword( 'DUPLICATE' );
				$this->skip_keyword( 'KEY' );
				$this->skip_keyword( 'UPDATE' );

				// Collect the update assignments.
				$conflict_update = $this->collect_to_eof();
				break;
			}

			$body .= WP_PgSQL_Token::TYPE_IDENTIFIER === $tok->type && str_starts_with( $tok->value, '`' )
				? '"' . trim( $tok->value, '`' ) . '"'
				: $tok->value;

			$this->advance();
		}

		$output = $fragment . $body;

		if ( $ignore ) {
			$output .= ' ON CONFLICT DO NOTHING';
		} elseif ( '' !== $conflict_update ) {
			// ON CONFLICT DO UPDATE — requires explicit target column(s).
			// We use a simplified approach here; the schema mapper provides column info.
			$output .= ' ON CONFLICT DO UPDATE SET ' . $conflict_update;
		}

		return $output;
	}

	/**
	 * Rewrite MySQL LIMIT x,y to PostgreSQL LIMIT y OFFSET x.
	 *
	 * @return string
	 */
	private function rewrite_limit(): string {
		$this->advance(); // consume LIMIT

		// Skip whitespace.
		$this->skip_whitespace();

		if ( $this->is_eof() ) {
			return 'LIMIT';
		}

		$first = $this->current()->value;
		$this->advance();
		$this->skip_whitespace();

		// If next token is a comma, MySQL LIMIT offset,count syntax.
		if ( ! $this->is_eof() && ',' === $this->current()->value ) {
			$this->advance(); // consume comma
			$this->skip_whitespace();
			$second = $this->current()->value;
			$this->advance();

			return "LIMIT {$second} OFFSET {$first}";
		}

		return "LIMIT {$first}";
	}

	/**
	 * Rewrite DESCRIBE/DESC table to information_schema query.
	 *
	 * @return string
	 */
	private function rewrite_describe(): string {
		$this->advance(); // consume DESCRIBE/DESC

		$this->skip_whitespace();

		if ( $this->is_eof() ) {
			return 'DESCRIBE';
		}

		$token = $this->current();

		// If next token is not a valid identifier (table name), treat as ORDER BY DESC.
		if ( ! $this->is_valid_table_name( $token ) ) {
			$this->unadvance(); // put the DESC back
			return 'DESC';
		}

		// Handle table name (with optional backticks or quotes).
		$table = trim( $token->value, '`"' );
		$this->advance();

		return "SELECT column_name, data_type, is_nullable, column_default FROM information_schema.columns WHERE table_name = '{$table}' ORDER BY ordinal_position";
	}

	/**
	 * Check if token is a valid table name for DESCRIBE.
	 *
	 * @param WP_PgSQL_Token $token Token to check.
	 *
	 * @return bool
	 */
	private function is_valid_table_name( WP_PgSQL_Token $token ): bool {
		// Valid table names: identifiers (including backtick-quoted), or strings.
		$type = $token->type;

		if ( WP_PgSQL_Token::TYPE_IDENTIFIER === $type ) {
			return true;
		}

		if ( WP_PgSQL_Token::TYPE_STRING === $type ) {
			return true;
		}

		return false;
	}

	/**
	 * Put the last token back onto the stream.
	 *
	 * @return void
	 */
	private function unadvance(): void {
		--$this->index;
	}

	// -------------------------------------------------------------------------
	// SHOW statement translation
	// -------------------------------------------------------------------------

	/**
	 * Translate MySQL SHOW statements to information_schema equivalents.
	 *
	 * @param string $sql Original SQL.
	 *
	 * @return string|null Translated SQL, or null if not a translatable SHOW.
	 */
	private function translate_show_statement( string $sql ): ?string {
		$upper = strtoupper( trim( $sql ) );

		// SHOW TABLES [LIKE 'pattern'].
		if ( preg_match( '/^SHOW\s+TABLES(?:\s+LIKE\s+\'([^\']+)\')?/i', $sql, $m ) ) {
			$like = isset( $m[1] ) ? " AND table_name LIKE '" . $m[1] . "'" : '';

			return "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'{$like} ORDER BY table_name";
		}

		// SHOW COLUMNS FROM `table` / SHOW FIELDS FROM `table`.
		if ( preg_match( '/^SHOW\s+(?:COLUMNS|FIELDS)\s+FROM\s+[`"]?(\w+)[`"]?/i', $sql, $m ) ) {
			$table = $m[1];

			return "SELECT column_name, data_type, is_nullable, column_default FROM information_schema.columns WHERE table_name = '{$table}' ORDER BY ordinal_position";
		}

		// SHOW CREATE TABLE.
		if ( preg_match( '/^SHOW\s+CREATE\s+TABLE\s+[`"]?(\w+)[`"]?/i', $sql, $m ) ) {
			$table = $m[1];
			return "SELECT 'CREATE TABLE (PostgreSQL)' AS \"Create Table\" FROM information_schema.tables WHERE table_name = '{$table}'";
		}

		// SHOW INDEX FROM `table`.
		if ( preg_match( '/^SHOW\s+(?:INDEX|INDEXES|KEYS)\s+FROM\s+[`"]?(\w+)[`"]?/i', $sql, $m ) ) {
			$table = $m[1];

			return "SELECT indexname AS Key_name, indexdef FROM pg_indexes WHERE tablename = '{$table}'";
		}

		// SHOW TABLE STATUS.
		if ( preg_match( '/^SHOW\s+TABLE\s+STATUS/i', $sql ) ) {
			return "SELECT table_name AS Name, 'InnoDB' AS Engine, 0 AS Data_length FROM information_schema.tables WHERE table_schema = 'public'";
		}

		// SHOW VARIABLES.
		if ( preg_match( '/^SHOW\s+(?:GLOBAL\s+|SESSION\s+)?VARIABLES/i', $sql ) ) {
			return 'SELECT name AS Variable_name, setting AS Value FROM pg_settings';
		}

		// DESCRIBE table / DESC table.
		if ( preg_match( '/^(?:DESCRIBE|DESC)\s+[`"]?(\w+)[`"]?/i', $sql, $m ) ) {
			$table = $m[1];

			return "SELECT column_name, data_type, is_nullable, column_default FROM information_schema.columns WHERE table_name = '{$table}' ORDER BY ordinal_position";
		}

		return null;
	}

	// -------------------------------------------------------------------------
	// Token stream helpers
	// -------------------------------------------------------------------------

	/**
	 * Return the current token without advancing.
	 *
	 * @return WP_PgSQL_Token
	 */
	private function current(): WP_PgSQL_Token {
		return $this->tokens[ $this->index ];
	}

	/**
	 * Advance the index by one.
	 *
	 * @return void
	 */
	private function advance(): void {
		++$this->index;
	}

	/**
	 * Whether the current token is EOF.
	 *
	 * @return bool
	 */
	private function is_eof(): bool {
		return WP_PgSQL_Token::TYPE_EOF === $this->tokens[ $this->index ]->type;
	}

	/**
	 * Skip whitespace tokens.
	 *
	 * @return void
	 */
	private function skip_whitespace(): void {
		while ( ! $this->is_eof() && WP_PgSQL_Token::TYPE_WHITESPACE === $this->current()->type ) {
			$this->advance();
		}
	}

	/**
	 * Skip a specific keyword (with any surrounding whitespace).
	 *
	 * @param string $keyword Keyword to skip.
	 *
	 * @return void
	 */
	private function skip_keyword( string $keyword ): void {
		$this->skip_whitespace();
		if ( ! $this->is_eof() && $this->current()->is_keyword( $keyword ) ) {
			$this->advance();
		}
	}

	/**
	 * Peek at the Nth significant (non-whitespace, non-comment) token ahead.
	 *
	 * @param int $offset Number of significant tokens to look ahead.
	 *
	 * @return WP_PgSQL_Token|null
	 */
	private function peek_significant( int $offset ): ?WP_PgSQL_Token {
		$count = 0;
		$i     = $this->index + 1;

		while ( isset( $this->tokens[ $i ] ) ) {
			if ( $this->tokens[ $i ]->is_significant() ) {
				++$count;
				if ( $count === $offset ) {
					return $this->tokens[ $i ];
				}
			}
			++$i;
		}

		return null;
	}

	/**
	 * Collect all remaining tokens into a raw string (used for conflict clauses).
	 *
	 * @return string
	 */
	private function collect_to_eof(): string {
		$out = '';
		while ( ! $this->is_eof() ) {
			$tok  = $this->current();
			$out .= WP_PgSQL_Token::TYPE_IDENTIFIER === $tok->type && str_starts_with( $tok->value, '`' )
				? '"' . trim( $tok->value, '`' ) . '"'
				: $tok->value;
			$this->advance();
		}

		return $out;
	}
}
