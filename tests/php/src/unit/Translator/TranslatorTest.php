<?php
/**
 * Unit tests for WP_PgSQL_Translator.
 *
 * @package WP_PgSQL_Database\Tests
 */

declare( strict_types=1 );

namespace WP_PgSQL_Database\Tests\Unit\Translator;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use WP_PgSQL_Database\Translator\WP_PgSQL_Lexer;
use WP_PgSQL_Database\Translator\WP_PgSQL_Translator;

/**
 * Class TranslatorTest
 *
 * @covers \WP_PgSQL_Database\Translator\WP_PgSQL_Translator
 */
class TranslatorTest extends TestCase {

	/**
	 * Translator under test.
	 *
	 * @var WP_PgSQL_Translator
	 */
	private $translator;

	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		$this->translator = new WP_PgSQL_Translator( new WP_PgSQL_Lexer() );
	}

	/**
	 * @inheritDoc
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function it_converts_backtick_identifiers_to_double_quoted(): void {
		$sql    = 'SELECT `ID` FROM `wp_posts`';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( '"ID"', $result );
		$this->assertStringContainsString( '"wp_posts"', $result );
		$this->assertStringNotContainsString( '`', $result );
	}

	/**
	 * @test
	 */
	public function it_drops_unsigned_modifier(): void {
		$sql    = 'SELECT col FROM t WHERE id UNSIGNED = 1';
		$result = $this->translator->translate( $sql );

		$this->assertStringNotContainsString( 'UNSIGNED', $result );
	}

	/**
	 * @test
	 */
	public function it_converts_insert_ignore_to_on_conflict_do_nothing(): void {
		$sql    = "INSERT IGNORE INTO `wp_options` (`option_name`, `option_value`) VALUES ('key', 'val')";
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'ON CONFLICT DO NOTHING', $result );
		$this->assertStringNotContainsString( 'IGNORE', $result );
	}

	/**
	 * @test
	 */
	public function it_rewrites_limit_offset_comma_syntax(): void {
		$sql    = 'SELECT * FROM wp_posts LIMIT 10,20';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'LIMIT 20 OFFSET 10', $result );
	}

	/**
	 * @test
	 */
	public function it_preserves_standard_limit_without_offset(): void {
		$sql    = 'SELECT * FROM wp_posts LIMIT 10';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'LIMIT 10', $result );
		$this->assertStringNotContainsString( 'OFFSET', $result );
	}

	/**
	 * @test
	 */
	public function it_translates_show_tables(): void {
		$sql    = 'SHOW TABLES';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'information_schema.tables', $result );
	}

	/**
	 * @test
	 */
	public function it_translates_show_columns_from(): void {
		$sql    = 'SHOW COLUMNS FROM wp_posts';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'information_schema.columns', $result );
		$this->assertStringContainsString( 'wp_posts', $result );
	}

	/**
	 * @test
	 */
	public function it_translates_regexp_to_tilde(): void {
		$sql    = "SELECT * FROM t WHERE col REGEXP '^foo'";
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( '~', $result );
		$this->assertStringNotContainsString( 'REGEXP', $result );
	}

	/**
	 * @test
	 */
	public function it_translates_ifnull_to_coalesce(): void {
		$sql    = 'SELECT IFNULL(col, 0) FROM t';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'COALESCE', $result );
		$this->assertStringNotContainsString( 'IFNULL', $result );
	}

	/**
	 * @test
	 */
	public function it_returns_empty_string_for_empty_input(): void {
		$this->assertSame( '', $this->translator->translate( '' ) );
	}

	/**
	 * @test
	 */
	public function it_passes_through_plain_postgresql_sql_unchanged(): void {
		$sql    = 'SELECT id, title FROM posts WHERE id = 1 ORDER BY id ASC LIMIT 10';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'SELECT', $result );
		$this->assertStringContainsString( 'FROM posts', $result );
		$this->assertStringContainsString( 'LIMIT 10', $result );
	}
}
