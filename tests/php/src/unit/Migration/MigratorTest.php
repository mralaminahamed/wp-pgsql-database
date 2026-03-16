<?php
/**
 * Unit tests for WP_PgSQL_Migrator.
 *
 * @package WP_PgSQL_Database\Tests
 */

declare( strict_types=1 );

namespace WP_PgSQL_Database\Tests\Unit\Migration;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use WP_PgSQL_Database\Migration\WP_PgSQL_Migrator;

/**
 * Class MigratorTest
 *
 * @covers \WP_PgSQL_Database\Migration\WP_PgSQL_Migrator
 */
class MigratorTest extends TestCase {

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
	public function run_does_nothing_when_already_at_version(): void {
		Functions\expect( 'get_option' )
			->once()
			->with( 'wp_pgsql_db_schema_version', '0.0.0' )
			->andReturn( WP_PGSQL_DB_VERSION );

		$migrator = new WP_PgSQL_Migrator();
		$migrator->run();
	}

	/**
	 * @test
	 */
	public function run_executes_migrations_when_needed(): void {
		Functions\expect( 'get_option' )
			->once()
			->with( 'wp_pgsql_db_schema_version', '0.0.0' )
			->andReturn( '0.0.0' );

		Functions\expect( 'update_option' )
			->twice()
			->andReturn( true );

		$migrator = new WP_PgSQL_Migrator();
		$migrator->run();
	}
}
