<?php
/**
 * Unit tests for WP_PgSQL_Migrator.
 *
 * @package WP_PgSQL_Database\Tests
 */

declare( strict_types=1 );

namespace WP_PgSQL_Database\Tests\Unit\Migration;

use ReflectionClass;
use WP_PgSQL_Database\Tests\Unit\WPPgSQLDatabaseTestCase;
use WP_PgSQL_Database\Migration\WP_PgSQL_Migrator;

/**
 * Class MigratorTest
 *
 * @covers \WP_PgSQL_Database\Migration\WP_PgSQL_Migrator
 */
class MigratorTest extends WPPgSQLDatabaseTestCase {

	/**
	 * Test that run() does nothing when already at current version.
	 *
	 * @test
	 */
	public function run_does_nothing_when_already_up_to_date(): void {
		$migrator = new WP_PgSQL_Migrator();

		update_option( 'wp_pgsql_db_schema_version', WP_PGSQL_DB_VERSION );

		$migrator->run();

		$this->assertEquals( WP_PGSQL_DB_VERSION, get_option( 'wp_pgsql_db_schema_version' ) );
	}

	/**
	 * Test that run() does nothing when version is higher than current.
	 *
	 * @test
	 */
	public function run_does_nothing_when_version_is_newer(): void {
		$migrator = new WP_PgSQL_Migrator();

		update_option( 'wp_pgsql_db_schema_version', '99.0.0' );

		$migrator->run();

		$this->assertEquals( '99.0.0', get_option( 'wp_pgsql_db_schema_version' ) );
	}

	/**
	 * Test that run() updates version option when no migrations exist yet.
	 *
	 * @test
	 */
	public function run_updates_version_when_no_migrations_needed(): void {
		$migrator = new WP_PgSQL_Migrator();

		update_option( 'wp_pgsql_db_schema_version', '0.0.0' );

		$migrator->run();

		$this->assertEquals( WP_PGSQL_DB_VERSION, get_option( 'wp_pgsql_db_schema_version' ) );
	}

	/**
	 * Test that get_pending_migrations returns empty when already at target.
	 *
	 * @test
	 */
	public function get_pending_migrations_returns_empty_when_current(): void {
		$migrator = new WP_PgSQL_Migrator();
		$reflection = new ReflectionClass( $migrator );
		$method = $reflection->getMethod( 'get_pending_migrations' );
		$method->setAccessible( true );

		$result = $method->invoke( $migrator, WP_PGSQL_DB_VERSION );

		$this->assertEmpty( $result );
	}

	/**
	 * Test that get_pending_migrations returns migrations for older version.
	 *
	 * @test
	 */
	public function get_pending_migrations_returns_migrations_for_old_version(): void {
		$migrator = new WP_PgSQL_Migrator();
		$reflection = new ReflectionClass( $migrator );
		$method = $reflection->getMethod( 'get_pending_migrations' );
		$method->setAccessible( true );

		$result = $method->invoke( $migrator, '0.0.0' );

		$this->assertArrayHasKey( '1.0.0', $result );
		$this->assertEquals( 'migrate_to_1_0_0', $result['1.0.0'] );
	}

	/**
	 * Test that migrate_to_1_0_0 method exists.
	 *
	 * @test
	 */
	public function migrate_to_1_0_0_method_exists(): void {
		$migrator = new WP_PgSQL_Migrator();

		$this->assertTrue( method_exists( $migrator, 'migrate_to_1_0_0' ) );
	}

	/**
	 * Test that VERSION_OPTION constant is defined correctly.
	 *
	 * @test
	 */
	public function version_option_constant_is_defined(): void {
		$reflection = new ReflectionClass( WP_PgSQL_Migrator::class );
		$constants = $reflection->getConstants();

		$this->assertArrayHasKey( 'VERSION_OPTION', $constants );
		$this->assertEquals( 'wp_pgsql_db_schema_version', $constants['VERSION_OPTION'] );
	}
}
