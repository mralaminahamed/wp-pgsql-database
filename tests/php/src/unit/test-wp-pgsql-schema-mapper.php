<?php
/**
 * Unit tests for WP_PgSQL_Schema_Mapper.
 *
 * @package WP_PgSQL_Database\Tests\Unit
 */

declare( strict_types=1 );

namespace WP_PgSQL_Database\Tests\unit;

use WP_PgSQL_Database\Schema\WP_PgSQL_Schema_Mapper;

/**
 * Class Test_WP_PgSQL_Schema_Mapper
 *
 * @covers \WP_PgSQL_Database\Schema\WP_PgSQL_Schema_Mapper
 */
class Test_WP_PgSQL_Schema_Mapper extends WP_PgSQL_Test_Case {

	/**
	 * Schema mapper under test.
	 *
	 * @var WP_PgSQL_Schema_Mapper
	 */
	private WP_PgSQL_Schema_Mapper $mapper;

	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->mapper = new WP_PgSQL_Schema_Mapper();
	}

	/**
	 * @test
	 */
	public function it_converts_bigint_auto_increment_to_bigserial(): void {
		$sql = 'CREATE TABLE t (id BIGINT NOT NULL AUTO_INCREMENT, PRIMARY KEY (id))';
		$result = $this->mapper->rewrite( $sql );

		$this->assertStringContainsString( 'BIGSERIAL', $result );
		$this->assertStringNotContainsString( 'AUTO_INCREMENT', $result );
	}

	/**
	 * @test
	 */
	public function it_converts_int_auto_increment_to_serial(): void {
		$sql    = 'CREATE TABLE t (id INT NOT NULL AUTO_INCREMENT)';
		$result = $this->mapper->rewrite( $sql );

		$this->assertStringContainsString( 'SERIAL', $result );
		$this->assertStringNotContainsString( 'AUTO_INCREMENT', $result );
	}

	/**
	 * @test
	 */
	public function it_converts_tinyint_1_to_boolean(): void {
		$sql    = 'CREATE TABLE t (active TINYINT(1) NOT NULL DEFAULT 0)';
		$result = $this->mapper->rewrite( $sql );

		$this->assertStringContainsString( 'BOOLEAN', $result );
		$this->assertStringNotContainsString( 'TINYINT(1)', $result );
	}

	/**
	 * @test
	 */
	public function it_converts_datetime_to_timestamp(): void {
		$sql    = 'CREATE TABLE t (created_at DATETIME NOT NULL)';
		$result = $this->mapper->rewrite( $sql );

		$this->assertStringContainsString( 'TIMESTAMP', $result );
		$this->assertStringNotContainsString( 'DATETIME', $result );
	}

	/**
	 * @test
	 */
	public function it_converts_longtext_to_text(): void {
		$sql    = 'CREATE TABLE t (body LONGTEXT)';
		$result = $this->mapper->rewrite( $sql );

		$this->assertStringContainsString( 'TEXT', $result );
		$this->assertStringNotContainsString( 'LONGTEXT', $result );
	}

	/**
	 * @test
	 */
	public function it_strips_engine_clause(): void {
		$sql    = 'CREATE TABLE t (id INT) ENGINE=InnoDB';
		$result = $this->mapper->rewrite( $sql );

		$this->assertStringNotContainsString( 'ENGINE', $result );
		$this->assertStringNotContainsString( 'InnoDB', $result );
	}

	/**
	 * @test
	 */
	public function it_strips_default_charset(): void {
		$sql    = "CREATE TABLE t (id INT) DEFAULT CHARSET=utf8mb4";
		$result = $this->mapper->rewrite( $sql );

		$this->assertStringNotContainsString( 'CHARSET', $result );
		$this->assertStringNotContainsString( 'utf8mb4', $result );
	}

	/**
	 * @test
	 */
	public function it_strips_unsigned_modifier(): void {
		$sql    = 'CREATE TABLE t (count INT UNSIGNED NOT NULL)';
		$result = $this->mapper->rewrite( $sql );

		$this->assertStringNotContainsString( 'UNSIGNED', $result );
	}

	/**
	 * @test
	 */
	public function it_converts_json_to_jsonb(): void {
		$sql    = 'CREATE TABLE t (meta JSON)';
		$result = $this->mapper->rewrite( $sql );

		$this->assertStringContainsString( 'JSONB', $result );
		$this->assertStringNotContainsString( 'JSON ', $result );
	}

	/**
	 * @test
	 */
	public function it_removes_fulltext_index_definitions(): void {
		$sql    = 'CREATE TABLE t (id INT, body TEXT, FULLTEXT KEY search_idx (body))';
		$result = $this->mapper->rewrite( $sql );

		$this->assertStringNotContainsString( 'FULLTEXT', $result );
	}
}
