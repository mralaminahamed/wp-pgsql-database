<?php
/**
 * Unit tests for WP_PgSQL_Installer.
 *
 * @package WP_PgSQL_Database\Tests\Unit
 */

declare( strict_types=1 );

namespace WP_PgSQL_Database\Tests\unit;

use Brain\Monkey\Functions;
use WP_PgSQL_Database\Migration\WP_PgSQL_Installer;
use function Brain\Monkey\Functions;

/**
 * Class Test_WP_PgSQL_Installer
 *
 * @covers \WP_PgSQL_Database\Migration\WP_PgSQL_Installer
 */
class Test_WP_PgSQL_Installer extends WP_PgSQL_Test_Case {

	/**
	 * Temporary directory for drop-in file operations.
	 *
	 * @var string
	 */
	private string $tmp_dir;

	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->tmp_dir = sys_get_temp_dir() . '/wp-pgsql-test-' . uniqid( '', true );
		mkdir( $this->tmp_dir, 0755, true );

		// Override drop-in destination to the temp directory.
		if ( ! defined( 'WP_PGSQL_DB_DROPIN_DEST' ) ) {
			define( 'WP_PGSQL_DB_DROPIN_DEST', $this->tmp_dir . '/db.php' );
		}

		// Stub WordPress functions used by WP_PgSQL_Installer.
		Functions\stubTranslationFunctions();
		Functions\expect( 'update_option' )->andReturn( true );
		Functions\expect( 'delete_option' )->andReturn( true );
		Functions\expect( 'current_time' )->andReturn( '2024-01-01 00:00:00' );
	}

	/**
	 * @inheritDoc
	 */
	protected function tearDown(): void {
		// Clean up temp files.
		if ( file_exists( $this->tmp_dir . '/db.php' ) ) {
			unlink( $this->tmp_dir . '/db.php' );
		}
		rmdir( $this->tmp_dir );

		parent::tearDown();
	}

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

	/**
	 * @test
	 */
	public function is_our_dropin_returns_false_for_foreign_dropin(): void {
		file_put_contents( $this->tmp_dir . '/db.php', '<?php // some other db drop-in' );

		$this->assertFalse( WP_PgSQL_Installer::is_our_dropin() );
	}

	/**
	 * @test
	 */
	public function is_our_dropin_returns_true_for_our_dropin(): void {
		file_put_contents(
			$this->tmp_dir . '/db.php',
			'<?php // WP PostgreSQL Database Drop-in — managed by plugin'
		);

		$this->assertTrue( WP_PgSQL_Installer::is_our_dropin() );
	}

	/**
	 * @test
	 */
	public function remove_dropin_returns_true_when_no_file_exists(): void {
		$this->assertTrue( WP_PgSQL_Installer::remove_dropin() );
	}
}
