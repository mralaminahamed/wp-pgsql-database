<?php
/**
 * MySQL SQL lexer.
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
 * Class WP_PgSQL_Lexer
 *
 * A single-pass, pure-PHP MySQL SQL lexer. Produces a flat array of
 * WP_PgSQL_Token objects that the translator consumes. Operates on byte
 * strings; does not perform multi-byte character awareness (MySQL identifiers
 * are ASCII-safe in practice for WordPress usage).
 */
class WP_PgSQL_Lexer {

	/**
	 * MySQL reserved keywords (subset relevant to WordPress/WooCommerce usage).
	 *
	 * @var array<string, true>
	 */
	private const KEYWORDS = array(
		'SELECT'         => true,
		'INSERT'         => true,
		'UPDATE'         => true,
		'DELETE'         => true,
		'CREATE'         => true,
		'DROP'           => true,
		'ALTER'          => true,
		'TABLE'          => true,
		'INDEX'          => true,
		'FROM'           => true,
		'WHERE'          => true,
		'AND'            => true,
		'OR'             => true,
		'NOT'            => true,
		'IN'             => true,
		'IS'             => true,
		'NULL'           => true,
		'LIKE'           => true,
		'BETWEEN'        => true,
		'EXISTS'         => true,
		'JOIN'           => true,
		'LEFT'           => true,
		'RIGHT'          => true,
		'INNER'          => true,
		'OUTER'          => true,
		'ON'             => true,
		'AS'             => true,
		'ORDER'          => true,
		'BY'             => true,
		'GROUP'          => true,
		'HAVING'         => true,
		'LIMIT'          => true,
		'OFFSET'         => true,
		'UNION'          => true,
		'ALL'            => true,
		'DISTINCT'       => true,
		'SET'            => true,
		'VALUES'         => true,
		'INTO'           => true,
		'DEFAULT'        => true,
		'PRIMARY'        => true,
		'KEY'            => true,
		'UNIQUE'         => true,
		'FOREIGN'        => true,
		'REFERENCES'     => true,
		'CASCADE'        => true,
		'CONSTRAINT'     => true,
		'ENGINE'         => true,
		'CHARSET'        => true,
		'COLLATE'        => true,
		'AUTO_INCREMENT' => true,
		'UNSIGNED'       => true,
		'SIGNED'         => true,
		'ZEROFILL'       => true,
		'IF'             => true,
		'EXISTS'         => true,
		'SHOW'           => true,
		'DESCRIBE'       => true,
		'EXPLAIN'        => true,
		'USE'            => true,
		'DATABASE'       => true,
		'DATABASES'      => true,
		'TABLES'         => true,
		'COLUMNS'        => true,
		'STATUS'         => true,
		'VARIABLES'      => true,
		'TRANSACTION'    => true,
		'BEGIN'          => true,
		'COMMIT'         => true,
		'ROLLBACK'       => true,
		'IGNORE'         => true,
		'REPLACE'        => true,
		'TRUNCATE'       => true,
		'RENAME'         => true,
		'ADD'            => true,
		'MODIFY'         => true,
		'CHANGE'         => true,
		'COLUMN'         => true,
		'AFTER'          => true,
		'BEFORE'         => true,
		'FIRST'          => true,
		'LAST'           => true,
		'ROW'            => true,
		'ROWS'           => true,
		'DUPLICATE'      => true,
		'CALL'           => true,
		'PROCEDURE'      => true,
		'FUNCTION'       => true,
		'TRIGGER'        => true,
		'VIEW'           => true,
		'CASE'           => true,
		'WHEN'           => true,
		'THEN'           => true,
		'ELSE'           => true,
		'END'            => true,
		'CAST'           => true,
		'CONVERT'        => true,
		'USING'          => true,
		'WITH'           => true,
		'RECURSIVE'      => true,
		'ASC'            => true,
		'DESC'           => true,
		'TRUE'           => true,
		'FALSE'          => true,
		'CROSS'          => true,
		'NATURAL'        => true,
		'FULL'           => true,
		'STRAIGHT_JOIN'  => true,
		'FORCE'          => true,
		'USE'            => true,
		'LOCK'           => true,
		'UNLOCK'         => true,
		'READ'           => true,
		'WRITE'          => true,
		'LOW_PRIORITY'   => true,
		'HIGH_PRIORITY'  => true,
		'DELAYED'        => true,
	);

	/**
	 * Source SQL string being lexed.
	 *
	 * @var string
	 */
	private string $source;

	/**
	 * Length of the source string.
	 *
	 * @var int
	 */
	private int $length;

	/**
	 * Current position within the source string.
	 *
	 * @var int
	 */
	private int $pos;

	/**
	 * Tokenise a MySQL SQL string.
	 *
	 * @param string $sql Raw MySQL SQL.
	 *
	 * @return WP_PgSQL_Token[]
	 */
	public function tokenise( string $sql ): array {
		$this->source = $sql;
		$this->length = strlen( $sql );
		$this->pos    = 0;

		$tokens = array();

		while ( $this->pos < $this->length ) {
			$token = $this->next_token();
			if ( null !== $token ) {
				$tokens[] = $token;
			}
		}

		$tokens[] = new WP_PgSQL_Token( WP_PgSQL_Token::TYPE_EOF, '', $this->pos );

		return $tokens;
	}

	/**
	 * Consume and return the next token from the source.
	 *
	 * @return WP_PgSQL_Token|null
	 */
	private function next_token(): ?WP_PgSQL_Token {
		$start = $this->pos;
		$char  = $this->source[ $this->pos ];

		// Whitespace.
		if ( ctype_space( $char ) ) {
			return $this->read_whitespace( $start );
		}

		// Single-line comment -- or #.
		if ( ( '-' === $char && isset( $this->source[ $this->pos + 1 ] ) && '-' === $this->source[ $this->pos + 1 ] ) || '#' === $char ) {
			return $this->read_line_comment( $start );
		}

		// Block comment /* ... */.
		if ( '/' === $char && isset( $this->source[ $this->pos + 1 ] ) && '*' === $this->source[ $this->pos + 1 ] ) {
			return $this->read_block_comment( $start );
		}

		// String literals: single-quoted or double-quoted.
		if ( '\'' === $char ) {
			return $this->read_single_quoted_string( $start );
		}

		// Backtick-quoted identifier.
		if ( '`' === $char ) {
			return $this->read_backtick_identifier( $start );
		}

		// Numeric literal.
		if ( ctype_digit( $char ) || ( '.' === $char && isset( $this->source[ $this->pos + 1 ] ) && ctype_digit( $this->source[ $this->pos + 1 ] ) ) ) {
			return $this->read_number( $start );
		}

		// Identifier or keyword.
		if ( ctype_alpha( $char ) || '_' === $char || '$' === $char ) {
			return $this->read_word( $start );
		}

		// Operators and punctuation (single-char fallthrough).
		++$this->pos;
		$type = in_array( $char, array( '(', ')', ',', ';', '.', '[', ']', '{', '}' ), true )
			? WP_PgSQL_Token::TYPE_PUNCTUATION
			: WP_PgSQL_Token::TYPE_OPERATOR;

		return new WP_PgSQL_Token( $type, $char, $start );
	}

	/**
	 * Read a run of whitespace characters.
	 *
	 * @param int $start Start offset.
	 *
	 * @return WP_PgSQL_Token
	 */
	private function read_whitespace( int $start ): WP_PgSQL_Token {
		while ( $this->pos < $this->length && ctype_space( $this->source[ $this->pos ] ) ) {
			++$this->pos;
		}

		return new WP_PgSQL_Token( WP_PgSQL_Token::TYPE_WHITESPACE, substr( $this->source, $start, $this->pos - $start ), $start );
	}

	/**
	 * Read a single-line comment (-- or #).
	 *
	 * @param int $start Start offset.
	 *
	 * @return WP_PgSQL_Token
	 */
	private function read_line_comment( int $start ): WP_PgSQL_Token {
		while ( $this->pos < $this->length && "\n" !== $this->source[ $this->pos ] ) {
			++$this->pos;
		}

		return new WP_PgSQL_Token( WP_PgSQL_Token::TYPE_COMMENT, substr( $this->source, $start, $this->pos - $start ), $start );
	}

	/**
	 * Read a block comment / * ... * /.
	 *
	 * @param int $start Start offset.
	 *
	 * @return WP_PgSQL_Token
	 */
	private function read_block_comment( int $start ): WP_PgSQL_Token {
		$this->pos += 2; // skip /*

		while ( $this->pos < $this->length ) {
			if ( '*' === $this->source[ $this->pos ] && isset( $this->source[ $this->pos + 1 ] ) && '/' === $this->source[ $this->pos + 1 ] ) {
				$this->pos += 2;
				break;
			}

			++$this->pos;
		}

		return new WP_PgSQL_Token( WP_PgSQL_Token::TYPE_COMMENT, substr( $this->source, $start, $this->pos - $start ), $start );
	}

	/**
	 * Read a single-quoted MySQL string, handling escape sequences.
	 *
	 * @param int $start Start offset.
	 *
	 * @return WP_PgSQL_Token
	 */
	private function read_single_quoted_string( int $start ): WP_PgSQL_Token {
		++$this->pos; // skip opening quote.

		while ( $this->pos < $this->length ) {
			$ch = $this->source[ $this->pos ];

			if ( '\\' === $ch ) {
				$this->pos += 2; // skip escaped character.
				continue;
			}

			++$this->pos;

			if ( '\'' === $ch ) {
				// Handle MySQL double-single-quote escape ''.
				if ( $this->pos < $this->length && '\'' === $this->source[ $this->pos ] ) {
					++$this->pos;
					continue;
				}
				break;
			}
		}

		return new WP_PgSQL_Token( WP_PgSQL_Token::TYPE_STRING, substr( $this->source, $start, $this->pos - $start ), $start );
	}

	/**
	 * Read a backtick-quoted identifier.
	 *
	 * @param int $start Start offset.
	 *
	 * @return WP_PgSQL_Token
	 */
	private function read_backtick_identifier( int $start ): WP_PgSQL_Token {
		++$this->pos; // skip opening backtick

		while ( $this->pos < $this->length && '`' !== $this->source[ $this->pos ] ) {
			++$this->pos;
		}

		++$this->pos; // skip closing backtick

		return new WP_PgSQL_Token( WP_PgSQL_Token::TYPE_IDENTIFIER, substr( $this->source, $start, $this->pos - $start ), $start );
	}

	/**
	 * Read a numeric literal (integer or decimal).
	 *
	 * @param int $start Start offset.
	 *
	 * @return WP_PgSQL_Token
	 */
	private function read_number( int $start ): WP_PgSQL_Token {
		$has_dot = false;

		while ( $this->pos < $this->length ) {
			$ch = $this->source[ $this->pos ];

			if ( ctype_digit( $ch ) ) {
				++$this->pos;
			} elseif ( '.' === $ch && ! $has_dot ) {
				$has_dot = true;
				++$this->pos;
			} else {
				break;
			}
		}

		return new WP_PgSQL_Token( WP_PgSQL_Token::TYPE_NUMBER, substr( $this->source, $start, $this->pos - $start ), $start );
	}

	/**
	 * Read a word token and classify it as keyword or identifier.
	 *
	 * @param int $start Start offset.
	 *
	 * @return WP_PgSQL_Token
	 */
	private function read_word( int $start ): WP_PgSQL_Token {
		while ( $this->pos < $this->length && ( ctype_alnum( $this->source[ $this->pos ] ) || '_' === $this->source[ $this->pos ] || '$' === $this->source[ $this->pos ] ) ) {
			++$this->pos;
		}

		$word = substr( $this->source, $start, $this->pos - $start );
		$type = isset( self::KEYWORDS[ strtoupper( $word ) ] )
			? WP_PgSQL_Token::TYPE_KEYWORD
			: WP_PgSQL_Token::TYPE_IDENTIFIER;

		return new WP_PgSQL_Token( $type, $word, $start );
	}
}
