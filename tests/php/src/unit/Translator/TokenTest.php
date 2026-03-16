<?php
/**
 * Unit tests for WP_PgSQL_Token.
 *
 * @package WP_PgSQL_Database\Tests
 */

declare( strict_types=1 );

namespace WP_PgSQL_Database\Tests\Unit\Translator;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use WP_PgSQL_Database\Translator\WP_PgSQL_Token;

/**
 * Class TokenTest
 *
 * @covers \WP_PgSQL_Database\Translator\WP_PgSQL_Token
 */
class TokenTest extends TestCase {

	/**
	 * Set up Brain\Monkey before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	/**
	 * Tear down Brain\Monkey after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function it_stores_type_value_and_offset(): void {
		$token = new WP_PgSQL_Token( WP_PgSQL_Token::TYPE_KEYWORD, 'SELECT', 10 );

		$this->assertSame( WP_PgSQL_Token::TYPE_KEYWORD, $token->type );
		$this->assertSame( 'SELECT', $token->value );
		$this->assertSame( 10, $token->offset );
	}

	/**
	 * @test
	 */
	public function it_normalises_value_to_uppercase(): void {
		$token = new WP_PgSQL_Token( WP_PgSQL_Token::TYPE_KEYWORD, 'select', 0 );

		$this->assertSame( 'SELECT', $token->normalised );
	}

	/**
	 * @test
	 */
	public function it_recognises_keyword_when_type_matches(): void {
		$token = new WP_PgSQL_Token( WP_PgSQL_Token::TYPE_KEYWORD, 'SELECT', 0 );

		$this->assertTrue( $token->is_keyword( 'SELECT' ) );
		$this->assertTrue( $token->is_keyword( 'select', 'INSERT' ) );
		$this->assertFalse( $token->is_keyword( 'INSERT' ) );
	}

	/**
	 * @test
	 */
	public function it_returns_false_for_is_keyword_when_not_a_keyword(): void {
		$token = new WP_PgSQL_Token( WP_PgSQL_Token::TYPE_IDENTIFIER, 'wp_posts', 0 );

		$this->assertFalse( $token->is_keyword( 'SELECT' ) );
	}

	/**
	 * @test
	 */
	public function it_recognises_significant_tokens(): void {
		$keyword = new WP_PgSQL_Token( WP_PgSQL_Token::TYPE_KEYWORD, 'SELECT', 0 );
		$ident   = new WP_PgSQL_Token( WP_PgSQL_Token::TYPE_IDENTIFIER, 'id', 0 );
		$string  = new WP_PgSQL_Token( WP_PgSQL_Token::TYPE_STRING, "'test'", 0 );
		$number  = new WP_PgSQL_Token( WP_PgSQL_Token::TYPE_NUMBER, '1', 0 );

		$this->assertTrue( $keyword->is_significant() );
		$this->assertTrue( $ident->is_significant() );
		$this->assertTrue( $string->is_significant() );
		$this->assertTrue( $number->is_significant() );
	}

	/**
	 * @test
	 */
	public function it_recognises_non_significant_tokens(): void {
		$whitespace = new WP_PgSQL_Token( WP_PgSQL_Token::TYPE_WHITESPACE, ' ', 0 );
		$comment    = new WP_PgSQL_Token( WP_PgSQL_Token::TYPE_COMMENT, '/* comment */', 0 );
		$eof        = new WP_PgSQL_Token( WP_PgSQL_Token::TYPE_EOF, '', 0 );

		$this->assertFalse( $whitespace->is_significant() );
		$this->assertFalse( $comment->is_significant() );
		$this->assertFalse( $eof->is_significant() );
	}

	/**
	 * @test
	 */
	public function it_returns_string_representation(): void {
		$token = new WP_PgSQL_Token( WP_PgSQL_Token::TYPE_KEYWORD, 'SELECT', 0 );

		$this->assertSame( '[KEYWORD:SELECT]', (string) $token );
	}

	/**
	 * @test
	 */
	public function it_has_type_constants(): void {
		$this->assertSame( 'KEYWORD', WP_PgSQL_Token::TYPE_KEYWORD );
		$this->assertSame( 'IDENTIFIER', WP_PgSQL_Token::TYPE_IDENTIFIER );
		$this->assertSame( 'STRING', WP_PgSQL_Token::TYPE_STRING );
		$this->assertSame( 'NUMBER', WP_PgSQL_Token::TYPE_NUMBER );
		$this->assertSame( 'OPERATOR', WP_PgSQL_Token::TYPE_OPERATOR );
		$this->assertSame( 'PUNCTUATION', WP_PgSQL_Token::TYPE_PUNCTUATION );
		$this->assertSame( 'WHITESPACE', WP_PgSQL_Token::TYPE_WHITESPACE );
		$this->assertSame( 'COMMENT', WP_PgSQL_Token::TYPE_COMMENT );
		$this->assertSame( 'EOF', WP_PgSQL_Token::TYPE_EOF );
	}
}
