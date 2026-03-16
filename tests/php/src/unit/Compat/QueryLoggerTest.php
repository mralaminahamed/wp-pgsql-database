<?php
/**
 * Unit tests for WP_PgSQL_Query_Logger.
 *
 * @package WP_PgSQL_Database\Tests
 */

declare( strict_types=1 );

namespace WP_PgSQL_Database\Tests\Unit\Compat;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use WP_PgSQL_Database\Compat\WP_PgSQL_Query_Logger;

/**
 * Class QueryLoggerTest
 *
 * @covers \WP_PgSQL_Database\Compat\WP_PgSQL_Query_Logger
 */
class QueryLoggerTest extends TestCase {

	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\stubTranslationFunctions();
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
	public function get_instance_returns_same_instance(): void {
		$instance1 = WP_PgSQL_Query_Logger::get_instance();
		$instance2 = WP_PgSQL_Query_Logger::get_instance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * @test
	 */
	public function constructor_registers_hooks(): void {
		Functions\expect( 'add_filter' )
			->once()
			->with( 'query', array( WP_PgSQL_Query_Logger::get_instance(), 'capture_query_start' ), 1 );

		Functions\expect( 'add_action' )
			->once()
			->with( 'shutdown', array( WP_PgSQL_Query_Logger::get_instance(), 'flush_to_log_file' ) );

		new WP_PgSQL_Query_Logger();
	}

	/**
	 * @test
	 */
	public function capture_query_start_returns_unchanged_sql(): void {
		$logger = WP_PgSQL_Query_Logger::get_instance();
		$sql    = 'SELECT * FROM wp_posts';

		$result = $logger->capture_query_start( $sql );

		$this->assertSame( $sql, $result );
	}

	/**
	 * @test
	 */
	public function capture_query_start_logs_query(): void {
		$logger = WP_PgSQL_Query_Logger::get_instance();
		$sql    = 'SELECT * FROM wp_posts';

		$logger->capture_query_start( $sql );
		$log = $logger->get_log();

		$this->assertCount( 1, $log );
		$this->assertSame( $sql, $log[0]['sql'] );
	}

	/**
	 * @test
	 */
	public function get_log_returns_empty_array_initially(): void {
		$logger = WP_PgSQL_Query_Logger::get_instance();

		$this->assertSame( array(), $logger->get_log() );
	}
}
