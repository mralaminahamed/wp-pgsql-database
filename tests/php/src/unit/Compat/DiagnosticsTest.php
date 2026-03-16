<?php
/**
 * Unit tests for WP_PgSQL_Diagnostics.
 *
 * @package WP_PgSQL_Database\Tests
 */

declare( strict_types=1 );

namespace WP_PgSQL_Database\Tests\Unit\Compat;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use WP_PgSQL_Database\Compat\WP_PgSQL_Diagnostics;

/**
 * Class DiagnosticsTest
 *
 * @covers \WP_PgSQL_Database\Compat\WP_PgSQL_Diagnostics
 */
class DiagnosticsTest extends TestCase {

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
		$instance1 = WP_PgSQL_Diagnostics::get_instance();
		$instance2 = WP_PgSQL_Diagnostics::get_instance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * @test
	 */
	public function constructor_registers_hooks(): void {
		Functions\expect( 'add_action' )
			->once()
			->with( 'admin_bar_menu', array( WP_PgSQL_Diagnostics::get_instance(), 'add_toolbar_node' ), 999 );

		Functions\expect( 'add_action' )
			->once()
			->with( 'wp_ajax_wp_pgsql_query_log', array( WP_PgSQL_Diagnostics::get_instance(), 'ajax_query_log' ) );

		new WP_PgSQL_Diagnostics();
	}
}
