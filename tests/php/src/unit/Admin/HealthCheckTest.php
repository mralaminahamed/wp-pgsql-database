<?php
/**
 * Unit tests for WP_PgSQL_Health_Check.
 *
 * @package WP_PgSQL_Database\Tests
 */

declare( strict_types=1 );

namespace WP_PgSQL_Database\Tests\Unit\Admin;

use WP_PgSQL_Database\Tests\Unit\WPPgSQLDatabaseTestCase;
use WP_PgSQL_Database\Admin\WP_PgSQL_Health_Check;

/**
 * Class HealthCheckTest
 *
 * @covers \WP_PgSQL_Database\Admin\WP_PgSQL_Health_Check
 */
class HealthCheckTest extends WPPgSQLDatabaseTestCase {

	/**
	 * @test
	 */
	public function get_instance_returns_same_instance(): void {
		$instance1 = WP_PgSQL_Health_Check::get_instance();
		$instance2 = WP_PgSQL_Health_Check::get_instance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * @test
	 */
	public function register_tests_adds_pgsql_tests(): void {
		$tests = array(
			'direct' => array(),
		);

		$health = WP_PgSQL_Health_Check::get_instance();
		$result = $health->register_tests( $tests );

		$this->assertArrayHasKey( 'wp_pgsql_dropin', $result['direct'] );
		$this->assertArrayHasKey( 'wp_pgsql_connection', $result['direct'] );
	}
}
