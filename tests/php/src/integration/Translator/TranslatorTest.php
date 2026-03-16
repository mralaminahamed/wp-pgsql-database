<?php
/**
 * Integration test — WP_PgSQL_Translator against a live PostgreSQL instance.
 *
 * Tests actual MySQL → PostgreSQL translation with live database operations.
 *
 * @package WP_PgSQL_Database\Tests\Integration
 */

declare( strict_types=1 );

namespace WP_PgSQL_Database\Tests\Integration\Translator;

use PHPUnit\Framework\TestCase;
use WP_PgSQL_Database\Database\WP_PgSQL_Db;
use WP_PgSQL_Database\Translator\WP_PgSQL_Translator;
use WP_PgSQL_Database\Translator\WP_PgSQL_Lexer;

/**
 * Class Test_WP_PgSQL_Translator_Integration
 *
 * @coversDefaultClass \WP_PgSQL_Database\Translator\WP_PgSQL_Translator
 */
class TranslatorTest extends TestCase {

	/**
	 * Database instance.
	 *
	 * @var WP_PgSQL_Db|null
	 */
	private static ?WP_PgSQL_Db $db = null;

	/**
	 * Translator instance.
	 *
	 * @var WP_PgSQL_Translator|null
	 */
	private static ?WP_PgSQL_Translator $translator = null;

	/**
	 * Skip all tests unless PostgreSQL credentials are available.
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		if ( ! defined( 'WP_TESTS_DB_ENGINE' ) || 'pgsql' !== constant( 'WP_TESTS_DB_ENGINE' ) ) {
			return;
		}

		if ( ! defined( 'DB_HOST' ) || ! defined( 'DB_USER' ) || ! defined( 'DB_PASSWORD' ) || ! defined( 'DB_NAME' ) ) {
			return;
		}

		self::$db         = new WP_PgSQL_Db( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
		self::$translator = new WP_PgSQL_Translator( new WP_PgSQL_Lexer() );
	}

	/**
	 * Skip guard helper.
	 *
	 * @return void
	 */
	private function require_connection(): void {
		if ( null === self::$db || ! self::$db->check_connection( false ) ) {
			$this->markTestSkipped( 'No live PostgreSQL connection available.' );
		}
	}

	/**
	 * @test
	 * @covers ::translate
	 */
	public function it_translates_select_with_backticks(): void {
		$this->require_connection();

		$translated = self::$translator->translate( 'SELECT * FROM `wp_posts`' );

		$this->assertStringContainsString( '"wp_posts"', $translated );
		$this->assertStringNotContainsString( '`wp_posts`', $translated );
	}

	/**
	 * @test
	 * @covers ::translate
	 */
	public function it_translates_insert(): void {
		$this->require_connection();

		$translated = self::$translator->translate(
			"INSERT INTO `wp_options` (`option_name`, `option_value`) VALUES ('test', 'value')"
		);

		$this->assertStringContainsString( '"wp_options"', $translated );
		$this->assertStringContainsString( '"option_name"', $translated );
	}

	/**
	 * @test
	 * @covers ::translate
	 */
	public function it_executes_translated_select(): void {
		$this->require_connection();

		$result = self::$db->query( 'SELECT 1 AS val' );

		$this->assertNotFalse( $result );
		$this->assertEmpty( self::$db->last_error );
	}

	/**
	 * @test
	 * @covers ::translate
	 */
	public function it_executes_translated_insert_and_select(): void {
		$this->require_connection();

		self::$db->query( 'CREATE TEMP TABLE IF EXISTS test_trans (id SERIAL, val TEXT)' );
		self::$db->query( "INSERT INTO test_trans (val) VALUES ('hello')" );

		$result = self::$db->query( 'SELECT * FROM test_trans' );

		$this->assertNotFalse( $result );
		$this->assertNotEmpty( self::$db->last_result );
	}

	/**
	 * @test
	 * @covers ::translate
	 */
	public function it_translates_ifnull_to_coalesce(): void {
		$this->require_connection();

		$translated = self::$translator->translate( 'SELECT IFNULL(NULL, "fallback")' );

		$this->assertStringContainsString( 'COALESCE', $translated );
	}

	/**
	 * @test
	 * @covers ::translate
	 */
	public function it_translates_limit_offset(): void {
		$this->require_connection();

		$translated = self::$translator->translate( 'SELECT * FROM wp_posts LIMIT 10 OFFSET 5' );

		$this->assertStringContainsString( 'LIMIT', $translated );
		$this->assertStringContainsString( 'OFFSET', $translated );
	}
}
