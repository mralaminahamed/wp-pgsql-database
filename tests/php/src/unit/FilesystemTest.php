<?php
/**
 * Unit tests for WP_PgSQL_Filesystem class.
 *
 * @package WP_PgSQL_Database
 * @subpackage Tests
 */

declare( strict_types=1 );

namespace WP_PgSQL_Database\Tests\Unit;

use Brain\Monkey;
use PHPUnit\Framework\TestCase;

/**
 * Test case for WP_PgSQL_Filesystem class.
 */
class FilesystemTest extends TestCase {

	/**
	 * Set up the test.
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	/**
	 * Tear down the test.
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Test singleton pattern.
	 */
	public function test_get_instance_returns_same_instance(): void {
		$instance1 = \WP_PgSQL_Database\WP_PgSQL_Filesystem::get_instance();
		$instance2 = \WP_PgSQL_Database\WP_PgSQL_Filesystem::get_instance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * Test is_initialized returns false before init.
	 */
	public function test_is_initialized_returns_false_before_init(): void {
		$filesystem = new \WP_PgSQL_Database\WP_PgSQL_Filesystem();

		$this->assertFalse( $filesystem->is_initialized() );
	}

	/**
	 * Test get_base_dir returns empty string when not initialized.
	 */
	public function test_get_base_dir_returns_empty_when_not_initialized(): void {
		$filesystem = new \WP_PgSQL_Database\WP_PgSQL_Filesystem();

		$this->assertSame( '', $filesystem->get_base_dir() );
	}

	/**
	 * Test exists returns false when not initialized.
	 */
	public function test_exists_returns_false_when_not_initialized(): void {
		$filesystem = new \WP_PgSQL_Database\WP_PgSQL_Filesystem();

		$this->assertFalse( $filesystem->exists( '/some/path' ) );
	}

	/**
	 * Test get_contents returns false when not initialized.
	 */
	public function test_get_contents_returns_false_when_not_initialized(): void {
		$filesystem = new \WP_PgSQL_Database\WP_PgSQL_Filesystem();

		$this->assertFalse( $filesystem->get_contents( '/some/path' ) );
	}

	/**
	 * Test put_contents returns false when not initialized.
	 */
	public function test_put_contents_returns_false_when_not_initialized(): void {
		$filesystem = new \WP_PgSQL_Database\WP_PgSQL_Filesystem();

		$this->assertFalse( $filesystem->put_contents( '/some/path', 'content' ) );
	}

	/**
	 * Test copy returns false when not initialized.
	 */
	public function test_copy_returns_false_when_not_initialized(): void {
		$filesystem = new \WP_PgSQL_Database\WP_PgSQL_Filesystem();

		$this->assertFalse( $filesystem->copy( 'source', 'dest' ) );
	}

	/**
	 * Test delete returns false when not initialized.
	 */
	public function test_delete_returns_false_when_not_initialized(): void {
		$filesystem = new \WP_PgSQL_Database\WP_PgSQL_Filesystem();

		$this->assertFalse( $filesystem->delete( '/some/path' ) );
	}

	/**
	 * Test is_dir returns false when not initialized.
	 */
	public function test_is_dir_returns_false_when_not_initialized(): void {
		$filesystem = new \WP_PgSQL_Database\WP_PgSQL_Filesystem();

		$this->assertFalse( $filesystem->is_dir( '/some/path' ) );
	}

	/**
	 * Test is_file returns false when not initialized.
	 */
	public function test_is_file_returns_false_when_not_initialized(): void {
		$filesystem = new \WP_PgSQL_Database\WP_PgSQL_Filesystem();

		$this->assertFalse( $filesystem->is_file( '/some/path' ) );
	}

	/**
	 * Test mtime returns false when not initialized.
	 */
	public function test_mtime_returns_false_when_not_initialized(): void {
		$filesystem = new \WP_PgSQL_Database\WP_PgSQL_Filesystem();

		$this->assertFalse( $filesystem->mtime( '/some/path' ) );
	}

	/**
	 * Test init returns false when WP_Filesystem function doesn't exist.
	 */
	public function test_init_returns_false_when_wp_filesystem_missing(): void {
		Monkey\Functions\when( 'request_filesystem_credentials' )->justReturn( array() );

		$filesystem = new \WP_PgSQL_Database\WP_PgSQL_Filesystem();

		$this->assertFalse( $filesystem->init() );
	}
}
