<?php
/**
 * Unit tests for WP_PgSQL_Admin.
 *
 * @package WP_PgSQL_Database\Tests
 */

declare( strict_types=1 );

namespace WP_PgSQL_Database\Tests\Unit\Admin;

use WP_PgSQL_Database\Tests\Unit\WPPgSQLDatabaseTestCase;
use WP_PgSQL_Database\Admin\WP_PgSQL_Admin;

/**
 * Class AdminTest
 *
 * @covers \WP_PgSQL_Database\Admin\WP_PgSQL_Admin
 */
class AdminTest extends WPPgSQLDatabaseTestCase {

	/**
	 * @test
	 */
	public function get_instance_returns_same_instance(): void {
		$instance1 = WP_PgSQL_Admin::get_instance();
		$instance2 = WP_PgSQL_Admin::get_instance();

		$this->assertSame( $instance1, $instance2 );
	}
}
