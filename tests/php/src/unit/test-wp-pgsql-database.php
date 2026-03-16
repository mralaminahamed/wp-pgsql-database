<?php
/**
 * Unit tests for WP_PgSQL_Database.
 *
 * @package WP_PgSQL_Database\Tests\Unit
 */

declare( strict_types=1 );

namespace WP_PgSQL_Database\Tests\unit;

use Brain\Monkey\Functions;
use WP_PgSQL_Database\WP_PgSQL_Database;
use function Brain\Monkey\Functions;

/**
 * Class Test_WP_PgSQL_Database
 *
 * @covers \WP_PgSQL_Database\WP_PgSQL_Database
 */
class Test_WP_PgSQL_Database extends WP_PgSQL_Test_Case {

	/**
	 * @test
	 */
	public function get_instance_returns_same_instance(): void {
		$instance1 = WP_PgSQL_Database::get_instance();
		$instance2 = WP_PgSQL_Database::get_instance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * @test
	 */
	public function init_registers_hooks(): void {
		Functions\stubTranslationFunctions();

		Functions\expect( 'add_action' )
			->once()
			->with( 'init', array( \WP_PgSQL_Database::get_instance(), 'load_textdomain' ) );

		Functions\expect( 'add_action' )
			->once()
			->with( 'admin_enqueue_scripts', array( \WP_PgSQL_Database::get_instance(), 'enqueue_admin_assets' ) );

		$plugin = WP_PgSQL_Database::get_instance();
		$plugin->init();
	}

	/**
	 * @test
	 */
	public function init_registers_admin_hooks_in_admin_context(): void {
		Functions\stubTranslationFunctions();
		Functions\when( 'is_admin' )->return( true );

		Functions\expect( 'add_action' )
			->once()
			->with( 'init', array( \WP_PgSQL_Database::get_instance(), 'boot_admin' ), 20 );

		$plugin = WP_PgSQL_Database::get_instance();
		$plugin->init();
	}

	/**
	 * @test
	 */
	public function init_does_not_register_admin_hooks_outside_admin(): void {
		Functions\stubTranslationFunctions();
		Functions\when( 'is_admin' )->return( false );

		Functions\expect( 'add_action' )
			->never()
			->with( 'init', array( \WP_PgSQL_Database::get_instance(), 'boot_admin' ), 20 );

		$plugin = WP_PgSQL_Database::get_instance();
		$plugin->init();
	}

	/**
	 * @test
	 */
	public function init_registers_health_check_hooks(): void {
		Functions\stubTranslationFunctions();

		Functions\expect( 'add_action' )
			->once()
			->with( 'init', array( \WP_PgSQL_Database::get_instance(), 'boot_health_check' ), 20 );

		$plugin = WP_PgSQL_Database::get_instance();
		$plugin->init();
	}

	/**
	 * @test
	 */
	public function init_registers_diagnostics_when_wp_debug_enabled(): void {
		Functions\stubTranslationFunctions();
		Functions\when( 'defined' )->returnArg();
		Functions\when( 'WP_DEBUG' )->return( true );

		Functions\expect( 'add_action' )
			->once()
			->with( 'init', array( \WP_PgSQL_Database::get_instance(), 'boot_diagnostics' ), 30 );

		$plugin = WP_PgSQL_Database::get_instance();
		$plugin->init();
	}

	/**
	 * @test
	 */
	public function init_does_not_register_diagnostics_when_wp_debug_disabled(): void {
		Functions\stubTranslationFunctions();
		Functions\when( 'defined' )->returnArg();
		Functions\when( 'WP_DEBUG' )->return( false );

		Functions\expect( 'add_action' )
			->never()
			->with( 'init', array( \WP_PgSQL_Database::get_instance(), 'boot_diagnostics' ), 30 );

		$plugin = WP_PgSQL_Database::get_instance();
		$plugin->init();
	}

	/**
	 * @test
	 */
	public function get_version_returns_version(): void {
		if ( ! defined( 'WP_PGSQL_DB_VERSION' ) ) {
			define( 'WP_PGSQL_DB_VERSION', '1.0.0' );
		}

		$plugin = WP_PgSQL_Database::get_instance();
		$this->assertSame( '1.0.0', $plugin->get_version() );
	}

	/**
	 * @test
	 */
	public function load_textdomain_calls_load_plugin_textdomain(): void {
		Functions\stubTranslationFunctions();
		Functions\expect( 'load_plugin_textdomain' )
			->once()
			->with( 'wp-pgsql-database', false, \WP_PGSQL_DB_PATH . 'languages' );

		$plugin = WP_PgSQL_Database::get_instance();
		$plugin->load_textdomain();
	}

	/**
	 * @test
	 */
	public function enqueue_admin_assets_returns_early_for_non_matching_hook(): void {
		Functions\stubTranslationFunctions();

		Functions\expect( 'wp_enqueue_style' )->never();
		Functions\expect( 'wp_enqueue_script' )->never();

		$plugin = WP_PgSQL_Database::get_instance();
		$plugin->enqueue_admin_assets( 'plugins.php' );
	}

	/**
	 * @test
	 */
	public function enqueue_admin_assets_enqueues_assets_for_matching_hook(): void {
		Functions\stubTranslationFunctions();

		Functions\expect( 'wp_enqueue_style' )
			->once()
			->with( 'wp-pgsql-database-admin', \WP_PGSQL_DB_URL . 'assets/css/admin.css', array(), '1.0.0' );

		Functions\expect( 'wp_enqueue_script' )
			->once()
			->with( 'wp-pgsql-database-admin', \WP_PGSQL_DB_URL . 'assets/js/admin.js', array( 'jquery' ), '1.0.0', true );

		Functions\expect( 'wp_localize_script' )
			->once()
			->with( 'wp-pgsql-database-admin', 'wpPgsqlDatabase', \WP_PGSQL_DB_URL . 'assets/js/admin.js' );

		$plugin = WP_PgSQL_Database::get_instance();
		$plugin->enqueue_admin_assets( 'tools_page_wp-pgsql-database' );
	}
}
