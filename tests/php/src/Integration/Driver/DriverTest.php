<?php
/**
 * Integration test — WP_PgSQL_Driver against a live PostgreSQL instance.
 *
 * These tests are skipped unless the DB_ENGINE=pgsql constant
 * is set and all DB_* constants point to a reachable PostgreSQL server.
 *
 * @package WP_PgSQL_Database\Tests\Integration
 */

declare( strict_types=1 );

namespace WP_PgSQL_Database\Tests\Integration\Driver;

use PHPUnit\Framework\TestCase;
use WP_PgSQL_Database\Driver\WP_PgSQL_Driver;

/**
 * Class Test_WP_PgSQL_Driver_Integration
 *
 * @coversDefaultClass \WP_PgSQL_Database\Driver\WP_PgSQL_Driver
 */
class DriverTest extends TestCase {

	/**
	 * Driver instance.
	 *
	 * @var WP_PgSQL_Driver|null
	 */
	private static ?WP_PgSQL_Driver $driver = null;

	/**
	 * Skip all tests unless PostgreSQL credentials are available.
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		if ( ! defined( 'DB_ENGINE' ) || 'pgsql' !== constant( 'DB_ENGINE' ) ) {
			return;
		}

		if ( ! defined( 'DB_HOST' ) || ! defined( 'DB_USER' ) || ! defined( 'DB_PASSWORD' ) || ! defined( 'DB_NAME' ) ) {
			return;
		}

		self::$driver = new WP_PgSQL_Driver();
		$connected    = self::$driver->connect( DB_HOST, DB_USER, DB_PASSWORD, DB_NAME );

		if ( ! $connected ) {
			self::$driver = null;
		}
	}

	/**
	 * Skip guard helper.
	 *
	 * @return void
	 */
	private function require_connection(): void {
		if ( null === self::$driver || ! self::$driver->is_connected() ) {
			$this->markTestSkipped( 'No live PostgreSQL connection available.' );
		}
	}

	/**
	 * @test
	 * @covers ::connect
	 */
	public function it_connects_successfully(): void {
		$this->require_connection();
		$this->assertTrue( self::$driver->is_connected() );
	}

	/**
	 * @test
	 * @covers ::get_server_version
	 */
	public function it_returns_server_version(): void {
		$this->require_connection();
		$version = self::$driver->get_server_version();

		$this->assertNotEmpty( $version );
		$this->assertStringContainsString( 'PostgreSQL', $version );
	}

	/**
	 * @test
	 * @covers ::query
	 */
	public function it_executes_select_query(): void {
		$this->require_connection();

		$result = self::$driver->query( 'SELECT 1 AS val' );

		$this->assertNotFalse( $result );
		$this->assertSame( 1, self::$driver->affected_rows() );
	}

	/**
	 * @test
	 * @covers ::get_results
	 */
	public function it_returns_query_results(): void {
		$this->require_connection();

		self::$driver->query( 'SELECT 1 AS val' );
		$results = self::$driver->get_results();

		$this->assertCount( 1, $results );
		$this->assertSame( '1', $results[0]->val );
	}

	/**
	 * @test
	 * @covers ::escape_string
	 */
	public function it_escapes_strings(): void {
		$this->require_connection();

		$escaped = self::$driver->escape_string( "test'value" );

		$this->assertStringContainsString( "'", $escaped );
		$this->assertStringNotContainsString( "''", $escaped );
	}

	/**
	 * @test
	 * @covers ::last_error
	 */
	public function it_reports_query_errors(): void {
		$this->require_connection();

		$result = self::$driver->query( 'SELECT * FROM nonexistent_table_xyz' );

		$this->assertFalse( $result );
		$this->assertNotEmpty( self::$driver->last_error() );
	}

	/**
	 * @test
	 * @covers ::begin_transaction
	 * @covers ::commit
	 */
	public function it_commits_transaction(): void {
		$this->require_connection();

		self::$driver->query( 'CREATE TEMP TABLE IF NOT EXISTS test_trx (id INT)' );
		$begun = self::$driver->begin_transaction();

		$this->assertTrue( $begun );

		self::$driver->query( "INSERT INTO test_trx (id) VALUES (1)" );
		$committed = self::$driver->commit();

		$this->assertTrue( $committed );
	}

	/**
	 * @test
	 * @covers ::begin_transaction
	 * @covers ::rollback
	 */
	public function it_rolls_back_transaction(): void {
		$this->require_connection();

		self::$driver->query( 'CREATE TEMP TABLE IF NOT EXISTS test_rbk (id INT)' );
		self::$driver->begin_transaction();
		self::$driver->query( "INSERT INTO test_rbk (id) VALUES (1)" );
		$rolled_back = self::$driver->rollback();

		$this->assertTrue( $rolled_back );
	}

	/**
	 * @test
	 * @covers ::insert_id
	 */
	public function it_returns_insert_id_after_insert(): void {
		$this->require_connection();

		self::$driver->query( 'CREATE TEMP TABLE test_seq (id SERIAL PRIMARY KEY, val TEXT)' );
		self::$driver->query( "INSERT INTO test_seq (val) VALUES ('test')" );

		$insert_id = self::$driver->insert_id();

		$this->assertGreaterThan( 0, $insert_id );
	}

	/**
	 * @test
	 * @covers ::close
	 */
	public function it_closes_connection(): void {
		$this->require_connection();

		self::$driver->close();

		$this->assertFalse( self::$driver->is_connected() );
	}
}
