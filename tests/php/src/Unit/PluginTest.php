<?php
/**
 * Unit tests for WP_PgSQL_Database (main plugin class).
 *
 * @package WP_PgSQL_Database\Tests
 */

declare( strict_types=1 );

namespace WP_PgSQL_Database\Tests\Unit;

use WP_PgSQL_Database\Tests\Unit\WPPgSQLDatabaseTestCase;
use WP_PgSQL_Database\WP_PgSQL_Database;

/**
 * Class DatabaseTest
 *
 * @covers \WP_PgSQL_Database\WP_PgSQL_Database
 */
class PluginTest extends WPPgSQLDatabaseTestCase {

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
	public function get_version_returns_version(): void {
		if ( ! defined( 'WP_PGSQL_DB_VERSION' ) ) {
			define( 'WP_PGSQL_DB_VERSION', '1.0.0' );
		}

		$plugin = WP_PgSQL_Database::get_instance();
		$this->assertSame( '1.0.0', $plugin->get_version() );
	}
}
