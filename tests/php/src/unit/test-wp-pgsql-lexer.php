<?php
/**
 * Unit tests for WP_PgSQL_Lexer.
 *
 * @package WP_PgSQL_Database\Tests\Unit
 */

declare( strict_types=1 );

namespace WP_PgSQL_Database\Tests\unit;

use WP_PgSQL_Database\Translator\WP_PgSQL_Lexer;
use WP_PgSQL_Database\Translator\WP_PgSQL_Token;

/**
 * Class Test_WP_PgSQL_Lexer
 *
 * @covers \WP_PgSQL_Database\Translator\WP_PgSQL_Lexer
 */
class Test_WP_PgSQL_Lexer extends WP_PgSQL_Test_Case {

	/**
	 * Lexer under test.
	 *
	 * @var WP_PgSQL_Lexer
	 */
	private WP_PgSQL_Lexer $lexer;

	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->lexer = new WP_PgSQL_Lexer();
	}

	/**
	 * @test
	 */
	public function it_tokenises_a_simple_select(): void {
		$tokens = $this->lexer->tokenise( 'SELECT 1' );

		$significant = array_values(
			array_filter( $tokens, fn( WP_PgSQL_Token $t ) => $t->is_significant() )
		);

		$this->assertSame( WP_PgSQL_Token::TYPE_KEYWORD, $significant[0]->type );
		$this->assertSame( 'SELECT', $significant[0]->normalised );
		$this->assertSame( WP_PgSQL_Token::TYPE_NUMBER, $significant[1]->type );
		$this->assertSame( '1', $significant[1]->value );
	}

	/**
	 * @test
	 */
	public function it_classifies_backtick_identifier(): void {
		$tokens = $this->lexer->tokenise( '`wp_posts`' );

		$this->assertSame( WP_PgSQL_Token::TYPE_IDENTIFIER, $tokens[0]->type );
		$this->assertSame( '`wp_posts`', $tokens[0]->value );
	}

	/**
	 * @test
	 */
	public function it_handles_single_quoted_strings(): void {
		$tokens = $this->lexer->tokenise( "'hello world'" );

		$this->assertSame( WP_PgSQL_Token::TYPE_STRING, $tokens[0]->type );
		$this->assertSame( "'hello world'", $tokens[0]->value );
	}

	/**
	 * @test
	 */
	public function it_handles_single_quoted_string_with_escaped_quote(): void {
		$tokens = $this->lexer->tokenise( "'it\\'s fine'" );

		$this->assertSame( WP_PgSQL_Token::TYPE_STRING, $tokens[0]->type );
	}

	/**
	 * @test
	 */
	public function it_skips_block_comments(): void {
		$tokens   = $this->lexer->tokenise( '/* comment */ SELECT 1' );
		$comments = array_filter( $tokens, fn( $t ) => WP_PgSQL_Token::TYPE_COMMENT === $t->type );

		$this->assertCount( 1, $comments );
	}

	/**
	 * @test
	 */
	public function it_skips_line_comments(): void {
		$tokens   = $this->lexer->tokenise( "-- comment\nSELECT 1" );
		$comments = array_filter( $tokens, fn( $t ) => WP_PgSQL_Token::TYPE_COMMENT === $t->type );

		$this->assertCount( 1, $comments );
	}

	/**
	 * @test
	 */
	public function it_always_terminates_with_eof(): void {
		$tokens = $this->lexer->tokenise( 'SELECT 1' );
		$last   = end( $tokens );

		$this->assertSame( WP_PgSQL_Token::TYPE_EOF, $last->type );
	}

	/**
	 * @test
	 */
	public function it_handles_empty_string(): void {
		$tokens = $this->lexer->tokenise( '' );

		$this->assertCount( 1, $tokens );
		$this->assertSame( WP_PgSQL_Token::TYPE_EOF, $tokens[0]->type );
	}

	/**
	 * @test
	 */
	public function it_handles_numeric_literals(): void {
		$tokens = $this->lexer->tokenise( '42 3.14' );

		$numbers = array_values(
			array_filter( $tokens, fn( $t ) => WP_PgSQL_Token::TYPE_NUMBER === $t->type )
		);

		$this->assertCount( 2, $numbers );
		$this->assertSame( '42', $numbers[0]->value );
		$this->assertSame( '3.14', $numbers[1]->value );
	}

	/**
	 * @test
	 */
	public function it_recognises_auto_increment_as_keyword(): void {
		$tokens = $this->lexer->tokenise( 'AUTO_INCREMENT' );

		$this->assertSame( WP_PgSQL_Token::TYPE_KEYWORD, $tokens[0]->type );
	}
}
