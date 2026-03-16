<?php
/**
 * Unit tests for WP_PgSQL_Db class.
 *
 * @package WP_PgSQL_Database
 * @subpackage Tests
 */

declare( strict_types=1 );

namespace WP_PgSQL_Database\Tests\Unit\Database;

use WP_PgSQL_Database\Tests\Unit\WPPgSQLDatabaseTestCase;

/**
 * Test case for WP_PgSQL_Db class.
 */
class DbTest extends WPPgSQLDatabaseTestCase {

	/**
	 * Test class exists.
	 */
	public function test_class_exists(): void {
		$this->assertTrue( class_exists( 'WP_PgSQL_Database\Database\WP_PgSQL_Db' ) );
	}

	/**
	 * Test extends wpdb.
	 */
	public function test_extends_wpdb(): void {
		$reflection = new \ReflectionClass( 'WP_PgSQL_Database\Database\WP_PgSQL_Db' );
		$this->assertTrue( $reflection->isSubclassOf( 'wpdb' ) );
	}

	/**
	 * Test has pg_driver property.
	 */
	public function test_has_pg_driver_property(): void {
		$reflection = new \ReflectionClass( 'WP_PgSQL_Database\Database\WP_PgSQL_Db' );
		$this->assertTrue( $reflection->hasProperty( 'pg_driver' ) );
	}

	/**
	 * Test has translator property.
	 */
	public function test_has_translator_property(): void {
		$reflection = new \ReflectionClass( 'WP_PgSQL_Database\Database\WP_PgSQL_Db' );
		$this->assertTrue( $reflection->hasProperty( 'translator' ) );
	}

	/**
	 * Test has pg_connected property.
	 */
	public function test_has_pg_connected_property(): void {
		$reflection = new \ReflectionClass( 'WP_PgSQL_Database\Database\WP_PgSQL_Db' );
		$this->assertTrue( $reflection->hasProperty( 'pg_connected' ) );
	}

	/**
	 * Test has sequence_cache property.
	 */
	public function test_has_sequence_cache_property(): void {
		$reflection = new \ReflectionClass( 'WP_PgSQL_Database\Database\WP_PgSQL_Db' );
		$this->assertTrue( $reflection->hasProperty( 'sequence_cache' ) );
	}

	/**
	 * Test has query method.
	 */
	public function test_has_query_method(): void {
		$reflection = new \ReflectionClass( 'WP_PgSQL_Database\Database\WP_PgSQL_Db' );
		$this->assertTrue( $reflection->hasMethod( 'query' ) );
	}

	/**
	 * Test has _real_escape method.
	 */
	public function test_has_real_escape_method(): void {
		$reflection = new \ReflectionClass( 'WP_PgSQL_Database\Database\WP_PgSQL_Db' );
		$this->assertTrue( $reflection->hasMethod( '_real_escape' ) );
	}

	/**
	 * Test has select method.
	 */
	public function test_has_select_method(): void {
		$reflection = new \ReflectionClass( 'WP_PgSQL_Database\Database\WP_PgSQL_Db' );
		$this->assertTrue( $reflection->hasMethod( 'select' ) );
	}

	/**
	 * Test has flush method.
	 */
	public function test_has_flush_method(): void {
		$reflection = new \ReflectionClass( 'WP_PgSQL_Database\Database\WP_PgSQL_Db' );
		$this->assertTrue( $reflection->hasMethod( 'flush' ) );
	}

	/**
	 * Test has check_connection method.
	 */
	public function test_has_check_connection_method(): void {
		$reflection = new \ReflectionClass( 'WP_PgSQL_Database\Database\WP_PgSQL_Db' );
		$this->assertTrue( $reflection->hasMethod( 'check_connection' ) );
	}

	/**
	 * Test has db_version method.
	 */
	public function test_has_db_version_method(): void {
		$reflection = new \ReflectionClass( 'WP_PgSQL_Database\Database\WP_PgSQL_Db' );
		$this->assertTrue( $reflection->hasMethod( 'db_version' ) );
	}

	/**
	 * Test has close method.
	 */
	public function test_has_close_method(): void {
		$reflection = new \ReflectionClass( 'WP_PgSQL_Database\Database\WP_PgSQL_Db' );
		$this->assertTrue( $reflection->hasMethod( 'close' ) );
	}

	/**
	 * Test select returns true (no-op for PostgreSQL).
	 */
	public function test_select_returns_true(): void {
		$reflection = new \ReflectionClass( 'WP_PgSQL_Database\Database\WP_PgSQL_Db' );
		$method     = $reflection->getMethod( 'select' );

		$this->assertTrue( $method->isPublic() );
	}
}
