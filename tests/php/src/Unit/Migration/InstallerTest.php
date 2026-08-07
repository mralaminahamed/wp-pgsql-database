<?php
/**
 * Unit tests for WP_PgSQL_Installer.
 *
 * @package WP_PgSQL_Database\Tests
 */

declare( strict_types=1 );

namespace WP_PgSQL_Database\Tests\Unit\Migration;

use WP_PgSQL_Database\Tests\Unit\WPPgSQLDatabaseTestCase;
use WP_PgSQL_Database\Migration\WP_PgSQL_Installer;

/**
 * Class InstallerTest
 *
 * @covers \WP_PgSQL_Database\Migration\WP_PgSQL_Installer
 */
class InstallerTest extends WPPgSQLDatabaseTestCase {

	/**
	 * @test
	 */
	public function is_dropin_active_returns_false_when_no_file_exists(): void {
		$this->assertFalse( WP_PgSQL_Installer::is_dropin_active() );
	}

	/**
	 * @test
	 */
	public function is_our_dropin_returns_false_for_non_existent_file(): void {
		$this->assertFalse( WP_PgSQL_Installer::is_our_dropin() );
	}
}
