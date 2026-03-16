<?php
/**
 * Unit tests for WP_PgSQL_Health_Check.
 *
 * @package WP_PgSQL_Database\Tests
 */

declare( strict_types=1 );

namespace WP_PgSQL_Database\Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use WP_PgSQL_Database\Admin\WP_PgSQL_Health_Check;

/**
 * Class HealthCheckTest
 *
 * @covers \WP_PgSQL_Database\Admin\WP_PgSQL_Health_Check
 */
class HealthCheckTest extends TestCase {

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

	/**
	 * @test
	 */
	public function test_dropin_returns_critical_when_not_installed(): void {
		Functions\when( 'WP_PgSQL_Installer::is_dropin_active' )->justReturn( false );

		$health = WP_PgSQL_Health_Check::get_instance();
		$result = $health->test_dropin();

		$this->assertSame( 'critical', $result['status'] );
	}

	/**
	 * @test
	 */
	public function test_dropin_returns_good_when_active(): void {
		Functions\when( 'WP_PgSQL_Installer::is_dropin_active' )->justReturn( true );
		Functions\when( 'WP_PgSQL_Installer::is_dropin_current' )->justReturn( true );

		$health = WP_PgSQL_Health_Check::get_instance();
		$result = $health->test_dropin();

		$this->assertSame( 'good', $result['status'] );
	}
}
