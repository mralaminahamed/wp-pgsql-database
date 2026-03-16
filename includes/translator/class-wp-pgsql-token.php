<?php
/**
 * SQL token value object.
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
 * Class WP_PgSQL_Token
 *
 * Immutable value object representing a single token emitted by the lexer.
 */
final class WP_PgSQL_Token {

	// Token type constants.
	public const TYPE_KEYWORD     = 'KEYWORD';
	public const TYPE_IDENTIFIER  = 'IDENTIFIER';
	public const TYPE_STRING      = 'STRING';
	public const TYPE_NUMBER      = 'NUMBER';
	public const TYPE_OPERATOR    = 'OPERATOR';
	public const TYPE_PUNCTUATION = 'PUNCTUATION';
	public const TYPE_WHITESPACE  = 'WHITESPACE';
	public const TYPE_COMMENT     = 'COMMENT';
	public const TYPE_EOF         = 'EOF';

	/**
	 * Token type (one of the TYPE_* constants above).
	 *
	 * @var string
	 */
	public string $type;

	/**
	 * Raw token value as it appeared in the source.
	 *
	 * @var string
	 */
	public string $value;

	/**
	 * Normalised (uppercase) token value for keyword matching.
	 *
	 * @var string
	 */
	public string $normalised;

	/**
	 * Byte offset of this token within the source string.
	 *
	 * @var int
	 */
	public int $offset;

	/**
	 * WP_PgSQL_Token constructor.
	 *
	 * @param string $type Token type.
	 * @param string $value Raw token value.
	 * @param int    $offset Byte offset in source.
	 */
	public function __construct( string $type, string $value, int $offset = 0 ) {
		$this->type       = $type;
		$this->value      = $value;
		$this->normalised = strtoupper( $value );
		$this->offset     = $offset;
	}

	/**
	 * Whether this token is a keyword matching the given value.
	 *
	 * @param string ...$keywords One or more keywords to test (case-insensitive).
	 *
	 * @return bool
	 */
	public function is_keyword( string ...$keywords ): bool {
		if ( self::TYPE_KEYWORD !== $this->type ) {
			return false;
		}

		foreach ( $keywords as $kw ) {
			if ( strtoupper( $kw ) === $this->normalised ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether this token is meaningful (not whitespace or comment).
	 *
	 * @return bool
	 */
	public function is_significant(): bool {
		return ! in_array( $this->type, array( self::TYPE_WHITESPACE, self::TYPE_COMMENT, self::TYPE_EOF ), true );
	}

	/**
	 * String representation for debugging.
	 *
	 * @return string
	 */
	public function __toString(): string {
		return sprintf( '[%s:%s]', $this->type, $this->value );
	}
}
