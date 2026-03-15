<?php
/**
 * Integration test — WP_PgSQL_Db against a live PostgreSQL instance.
 *
 * These tests are skipped unless the WP_TESTS_DB_ENGINE=pgsql constant
 * is set and all DB_* constants point to a reachable PostgreSQL server.
 *
 * @package WP_PgSQL_Database\Tests\Integration
 */

declare( strict_types=1 );

namespace WP_PgSQL_Database\Tests\integration;

use PHPUnit\Framework\TestCase;
use WP_PgSQL_Database\Database\WP_PgSQL_Db;

/**
 * Class Test_WP_PgSQL_Db_Integration
 *
 * @coversDefaultClass \WP_PgSQL_Database\Database\WP_PgSQL_Db
 */
class Test_WP_PgSQL_Db_Integration extends TestCase {

	/**
	 * Database instance.
	 *
	 * @var WP_PgSQL_Db|null
	 */
	private static ?WP_PgSQL_Db $db = null;

	/**
	 * Skip all tests unless PostgreSQL credentials are available.
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		if ( ! defined( 'WP_TESTS_DB_ENGINE' ) || 'pgsql' !== WP_TESTS_DB_ENGINE ) {
			return;
		}

		if ( ! defined( 'DB_HOST' ) || ! defined( 'DB_USER' ) || ! defined( 'DB_PASSWORD' ) || ! defined( 'DB_NAME' ) ) {
			return;
		}

		self::$db = new WP_PgSQL_Db( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
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
	 * @covers ::check_connection
	 */
	public function it_connects_successfully(): void {
		$this->require_connection();
		$this->assertTrue( self::$db->check_connection( false ) );
	}

	/**
	 * @test
	 * @covers ::db_version
	 */
	public function it_returns_server_version(): void {
		$this->require_connection();
		$version = self::$db->db_version();

		$this->assertNotEmpty( $version );
		$this->assertStringContainsString( 'PostgreSQL', $version );
	}

	/**
	 * @test
	 * @covers ::query
	 */
	public function it_executes_select_one(): void {
		$this->require_connection();

		$result = self::$db->query( 'SELECT 1 AS val' );

		$this->assertSame( 1, $result );
		$this->assertCount( 1, self::$db->last_result );
		$this->assertSame( '1', self::$db->last_result[0]->val );
	}

	/**
	 * @test
	 * @covers ::query
	 */
	public function it_handles_show_tables_translation(): void {
		$this->require_connection();

		$result = self::$db->query( 'SHOW TABLES' );

		// Result may be 0 on a fresh database; the important thing is no error.
		$this->assertNotFalse( $result );
		$this->assertEmpty( self::$db->last_error );
	}
}
