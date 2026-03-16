<?php
/**
 * Unit tests for WP_PgSQL_Driver.
 *
 * @package WP_PgSQL_Database\Tests
 */

declare( strict_types=1 );

namespace WP_PgSQL_Database\Tests\Unit\Driver;

use WP_PgSQL_Database\Tests\Unit\WPPgSQLDatabaseTestCase;
use WP_PgSQL_Database\Driver\WP_PgSQL_Driver;

/**
 * Class DriverTest
 *
 * @covers \WP_PgSQL_Database\Driver\WP_PgSQL_Driver
 */
class DriverTest extends WPPgSQLDatabaseTestCase {

	/**
	 * Driver under test.
	 *
	 * @var WP_PgSQL_Driver
	 */
	private $driver;

	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->driver = new WP_PgSQL_Driver();
	}

	/**
	 * @test
	 */
	public function it_starts_disconnected(): void {
		$this->assertFalse( $this->driver->is_connected() );
	}

	/**
	 * @test
	 */
	public function it_returns_empty_error_when_not_connected(): void {
		$this->assertSame( '', $this->driver->last_error() );
	}

	/**
	 * @test
	 */
	public function it_returns_zero_affected_rows_when_not_connected(): void {
		$this->assertSame( 0, $this->driver->affected_rows() );
	}

	/**
	 * @test
	 */
	public function it_returns_zero_insert_id_when_not_connected(): void {
		$this->assertSame( 0, $this->driver->insert_id() );
	}

	/**
	 * @test
	 */
	public function it_returns_empty_results_when_not_connected(): void {
		$this->assertSame( array(), $this->driver->get_results() );
	}

	/**
	 * @test
	 */
	public function it_returns_empty_version_when_not_connected(): void {
		$this->assertSame( '', $this->driver->get_server_version() );
	}

	/**
	 * @test
	 */
	public function it_returns_false_for_begin_transaction_when_not_connected(): void {
		$this->assertFalse( $this->driver->begin_transaction() );
	}

	/**
	 * @test
	 */
	public function it_returns_false_for_commit_when_not_connected(): void {
		$this->assertFalse( $this->driver->commit() );
	}

	/**
	 * @test
	 */
	public function it_returns_false_for_rollback_when_not_connected(): void {
		$this->assertFalse( $this->driver->rollback() );
	}

	/**
	 * @test
	 */
	public function it_escapes_string_using_addslashes_when_not_connected(): void {
		$input  = "test's string";
		$result = $this->driver->escape_string( $input );

		$this->assertSame( "test\\'s string", $result );
	}

	/**
	 * @test
	 */
	public function it_returns_false_for_query_when_not_connected(): void {
		$result = $this->driver->query( 'SELECT 1' );

		$this->assertFalse( $result );
		$this->assertSame( 'No active database connection.', $this->driver->last_error() );
	}

	/**
	 * @test
	 */
	public function it_returns_false_for_close_when_not_connected(): void {
		$this->driver->close();

		$this->assertFalse( $this->driver->is_connected() );
	}
}
