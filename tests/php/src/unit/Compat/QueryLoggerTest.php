<?php
/**
 * Unit tests for WP_PgSQL_Query_Logger.
 *
 * @package WP_PgSQL_Database\Tests
 */

declare( strict_types=1 );

namespace WP_PgSQL_Database\Tests\Unit\Compat;

use WP_PgSQL_Database\Tests\Unit\WPPgSQLDatabaseTestCase;
use WP_PgSQL_Database\Compat\WP_PgSQL_Query_Logger;

/**
 * Class QueryLoggerTest
 *
 * @covers \WP_PgSQL_Database\Compat\WP_PgSQL_Query_Logger
 */
class QueryLoggerTest extends WPPgSQLDatabaseTestCase {

	/**
	 * @test
	 */
	public function get_instance_returns_same_instance(): void {
		$instance1 = WP_PgSQL_Query_Logger::get_instance();
		$instance2 = WP_PgSQL_Query_Logger::get_instance();

		$this->assertSame( $instance1, $instance2 );
	}
}
