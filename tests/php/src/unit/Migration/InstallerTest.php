<?php
/**
 * Unit tests for WP_PgSQL_Installer.
 *
 * @package WP_PgSQL_Database\Tests
 */

declare( strict_types=1 );

namespace WP_PgSQL_Database\Tests\Unit\Migration;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use WP_PgSQL_Database\Migration\WP_PgSQL_Installer;

/**
 * Class InstallerTest
 *
 * @covers \WP_PgSQL_Database\Migration\WP_PgSQL_Installer
 */
class InstallerTest extends TestCase {

	/**
	 * Temporary directory for drop-in file operations.
	 *
	 * @var string
	 */
	private $tmp_dir;

	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		Functions\stubTranslationFunctions();
		Functions\expect( 'update_option' )->andReturn( true );
		Functions\expect( 'delete_option' )->andReturn( true );
		Functions\expect( 'current_time' )->andReturn( '2024-01-01 00:00:00' );

		$this->tmp_dir = sys_get_temp_dir() . '/wp-pgsql-test-' . uniqid( '', true );
		mkdir( $this->tmp_dir, 0755, true );

		if ( ! defined( 'WP_PGSQL_DB_DROPIN_DEST' ) ) {
			define( 'WP_PGSQL_DB_DROPIN_DEST', $this->tmp_dir . '/db.php' );
		}
	}

	/**
	 * @inheritDoc
	 */
	protected function tearDown(): void {
		if ( file_exists( $this->tmp_dir . '/db.php' ) ) {
			unlink( $this->tmp_dir . '/db.php' );
		}
		rmdir( $this->tmp_dir );

		Monkey\tearDown();
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
