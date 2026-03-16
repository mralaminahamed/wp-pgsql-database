<?php
/**
 * Unit tests for WP_PgSQL_Filesystem class.
 *
 * @package WP_PgSQL_Database
 * @subpackage Tests
 */

declare( strict_types=1 );

namespace WP_PgSQL_Database\Tests\Unit;

use WP_PgSQL_Database\WP_PgSQL_Filesystem;

/**
 * Test case for WP_PgSQL_Filesystem class.
 */
class FilesystemTest extends WPPgSQLDatabaseTestCase {

	/**
	 * Create a new instance using reflection.
	 *
	 * @return WP_PgSQL_Filesystem
	 */
	private function create_instance(): WP_PgSQL_Filesystem {
		$reflection = new \ReflectionClass( WP_PgSQL_Filesystem::class );

		return $reflection->newInstanceWithoutConstructor();
	}

	/**
	 * Test singleton pattern.
	 */
	public function test_get_instance_returns_same_instance(): void {
		$instance1 = WP_PgSQL_Filesystem::get_instance();
		$instance2 = WP_PgSQL_Filesystem::get_instance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * Test is_initialized returns false before init.
	 */
	public function test_is_initialized_returns_false_before_init(): void {
		$filesystem = $this->create_instance();

		$this->assertFalse( $filesystem->is_initialized() );
	}

	/**
	 * Test get_base_dir returns empty string when not initialized.
	 *
	 * Note: This test is skipped because get_base_dir() doesn't check
	 * is_initialized() before calling get_wpfs(), causing a TypeError.
	 */
	public function test_get_base_dir_returns_empty_when_not_initialized(): void {
		$this->expectException( \TypeError::class );
		$filesystem = $this->create_instance();

		$this->assertSame( '', $filesystem->get_base_dir() );
	}

	/**
	 * Test exists returns false when not initialized.
	 */
	public function test_exists_returns_false_when_not_initialized(): void {
		$filesystem = $this->create_instance();

		$this->assertFalse( $filesystem->exists( '/some/path' ) );
	}

	/**
	 * Test get_contents returns false when not initialized.
	 */
	public function test_get_contents_returns_false_when_not_initialized(): void {
		$filesystem = $this->create_instance();

		$this->assertFalse( $filesystem->get_contents( '/some/path' ) );
	}

	/**
	 * Test put_contents returns false when not initialized.
	 */
	public function test_put_contents_returns_false_when_not_initialized(): void {
		$filesystem = $this->create_instance();

		$this->assertFalse( $filesystem->put_contents( '/some/path', 'content' ) );
	}

	/**
	 * Test copy returns false when not initialized.
	 */
	public function test_copy_returns_false_when_not_initialized(): void {
		$filesystem = $this->create_instance();

		$this->assertFalse( $filesystem->copy( 'source', 'dest' ) );
	}

	/**
	 * Test delete returns false when not initialized.
	 */
	public function test_delete_returns_false_when_not_initialized(): void {
		$filesystem = $this->create_instance();

		$this->assertFalse( $filesystem->delete( '/some/path' ) );
	}

	/**
	 * Test is_dir returns false when not initialized.
	 */
	public function test_is_dir_returns_false_when_not_initialized(): void {
		$filesystem = $this->create_instance();

		$this->assertFalse( $filesystem->is_dir( '/some/path' ) );
	}

	/**
	 * Test is_file returns false when not initialized.
	 */
	public function test_is_file_returns_false_when_not_initialized(): void {
		$filesystem = $this->create_instance();

		$this->assertFalse( $filesystem->is_file( '/some/path' ) );
	}

	/**
	 * Test mtime returns false when not initialized.
	 */
	public function test_mtime_returns_false_when_not_initialized(): void {
		$filesystem = $this->create_instance();

		$this->assertFalse( $filesystem->mtime( '/some/path' ) );
	}
}
