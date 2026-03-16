<?php
/**
 * Unit tests for WP_PgSQL_Admin.
 *
 * @package WP_PgSQL_Database\Tests
 */

declare( strict_types=1 );

namespace WP_PgSQL_Database\Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use WP_PgSQL_Database\Admin\WP_PgSQL_Admin;

/**
 * Class AdminTest
 *
 * @covers \WP_PgSQL_Database\Admin\WP_PgSQL_Admin
 */
class AdminTest extends TestCase {

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
		$instance1 = WP_PgSQL_Admin::get_instance();
		$instance2 = WP_PgSQL_Admin::get_instance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * @test
	 */
	public function register_menu_adds_management_page(): void {
		Functions\expect( 'add_management_page' )
			->once()
			->with(
				'PostgreSQL Database',
				'PostgreSQL DB',
				'manage_options',
				'wp-pgsql-database',
				array( WP_PgSQL_Admin::get_instance(), 'render_page' )
			)
			->andReturn( 'tools_page_wp-pgsql-database' );

		$admin = WP_PgSQL_Admin::get_instance();
		$admin->register_menu();
	}

	/**
	 * @test
	 */
	public function get_page_hook_returns_string(): void {
		Functions\expect( 'add_management_page' )
			->once()
			->andReturn( 'tools_page_wp-pgsql-database' );

		$admin = WP_PgSQL_Admin::get_instance();
		$admin->register_menu();

		$this->assertSame( 'tools_page_wp-pgsql-database', $admin->get_page_hook() );
	}
}
