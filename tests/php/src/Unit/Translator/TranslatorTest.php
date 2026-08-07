<?php
/**
 * Unit tests for WP_PgSQL_Translator.
 *
 * @package WP_PgSQL_Database\Tests
 */

declare( strict_types=1 );

namespace WP_PgSQL_Database\Tests\Unit\Translator;

use WP_PgSQL_Database\Tests\Unit\WPPgSQLDatabaseTestCase;
use WP_PgSQL_Database\Translator\WP_PgSQL_Lexer;
use WP_PgSQL_Database\Translator\WP_PgSQL_Translator;

/**
 * Class TranslatorTest
 *
 * @covers \WP_PgSQL_Database\Translator\WP_PgSQL_Translator
 */
class TranslatorTest extends WPPgSQLDatabaseTestCase {

	/**
	 * Translator under test.
	 *
	 * @var WP_PgSQL_Translator
	 */
	private $translator;

	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->translator = new WP_PgSQL_Translator( new WP_PgSQL_Lexer() );
	}

	/**
	 * @test
	 */
	public function it_converts_backtick_identifiers_to_double_quoted(): void {
		$sql    = 'SELECT `ID` FROM `wp_posts`';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( '"ID"', $result );
		$this->assertStringContainsString( '"wp_posts"', $result );
		$this->assertStringNotContainsString( '`', $result );
	}

	/**
	 * @test
	 */
	public function it_drops_unsigned_modifier(): void {
		$sql    = 'SELECT col FROM t WHERE id UNSIGNED = 1';
		$result = $this->translator->translate( $sql );

		$this->assertStringNotContainsString( 'UNSIGNED', $result );
	}

	/**
	 * @test
	 */
	public function it_converts_insert_ignore_to_on_conflict_do_nothing(): void {
		$sql    = "INSERT IGNORE INTO `wp_options` (`option_name`, `option_value`) VALUES ('key', 'val')";
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'ON CONFLICT DO NOTHING', $result );
		$this->assertStringNotContainsString( 'IGNORE', $result );
	}

	/**
	 * @test
	 */
	public function it_rewrites_limit_offset_comma_syntax(): void {
		$sql    = 'SELECT * FROM wp_posts LIMIT 10,20';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'LIMIT 20 OFFSET 10', $result );
	}

	/**
	 * @test
	 */
	public function it_preserves_standard_limit_without_offset(): void {
		$sql    = 'SELECT * FROM wp_posts LIMIT 10';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'LIMIT 10', $result );
		$this->assertStringNotContainsString( 'OFFSET', $result );
	}

	/**
	 * @test
	 */
	public function it_translates_show_tables(): void {
		$sql    = 'SHOW TABLES';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'information_schema.tables', $result );
	}

	/**
	 * @test
	 */
	public function it_translates_show_columns_from(): void {
		$sql    = 'SHOW COLUMNS FROM wp_posts';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'information_schema.columns', $result );
		$this->assertStringContainsString( 'wp_posts', $result );
	}

	/**
	 * @test
	 */
	public function it_translates_regexp_to_tilde(): void {
		$sql    = "SELECT * FROM t WHERE col REGEXP '^foo'";
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( '~', $result );
		$this->assertStringNotContainsString( 'REGEXP', $result );
	}

	/**
	 * @test
	 */
	public function it_translates_ifnull_to_coalesce(): void {
		$sql    = 'SELECT IFNULL(col, 0) FROM t';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'COALESCE', $result );
		$this->assertStringNotContainsString( 'IFNULL', $result );
	}

	/**
	 * @test
	 */
	public function it_returns_empty_string_for_empty_input(): void {
		$this->assertSame( '', $this->translator->translate( '' ) );
	}

	/**
	 * @test
	 */
	public function it_passes_through_plain_postgresql_sql_unchanged(): void {
		$sql    = 'SELECT id, title FROM posts WHERE id = 1 ORDER BY id ASC LIMIT 10';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'SELECT', $result );
		$this->assertStringContainsString( 'FROM posts', $result );
		$this->assertStringContainsString( 'LIMIT 10', $result );
	}

	/**
	 * @test
	 */
	public function it_handles_insert_without_ignore(): void {
		$sql    = "INSERT INTO `wp_options` (`option_name`) VALUES ('test')";
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'INSERT INTO', $result );
		$this->assertStringNotContainsString( 'ON CONFLICT', $result );
	}

	/**
	 * @test
	 */
	public function it_handles_insert_with_on_duplicate_key(): void {
		$sql    = "INSERT INTO `wp_options` (`option_name`, `option_value`) VALUES ('key', 'val') ON DUPLICATE KEY UPDATE `option_value` = 'new'";
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'ON CONFLICT DO UPDATE', $result );
	}

	/**
	 * @test
	 */
	public function it_handles_limit_with_offset_keyword(): void {
		$sql    = 'SELECT * FROM wp_posts LIMIT 10 OFFSET 5';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'LIMIT 10', $result );
		$this->assertStringContainsString( 'OFFSET 5', $result );
	}

	/**
	 * @test
	 */
	public function it_translates_rlike_to_tilde(): void {
		$sql    = "SELECT * FROM t WHERE col RLIKE '^foo'";
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( '~', $result );
		$this->assertStringNotContainsString( 'RLIKE', $result );
	}

	/**
	 * @test
	 */
	public function it_translates_isnull_to_is_null(): void {
		$sql    = 'SELECT * FROM t WHERE col ISNULL';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( '( IS NULL )', $result );
	}

	/**
	 * @test
	 */
	public function it_handles_show_create_table(): void {
		$sql    = 'SHOW CREATE TABLE wp_posts';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'information_schema', $result );
	}

	/**
	 * @test
	 */
	public function it_handles_show_index(): void {
		$sql    = 'SHOW INDEX FROM wp_posts';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'pg_indexes', $result );
	}

	/**
	 * @test
	 */
	public function it_handles_show_table_status(): void {
		$sql    = 'SHOW TABLE STATUS';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'information_schema', $result );
	}

	/**
	 * @test
	 */
	public function it_handles_show_variables(): void {
		$sql    = 'SHOW VARIABLES';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'pg_settings', $result );
	}

	/**
	 * @test
	 */
	public function it_handles_describe_keyword(): void {
		$sql    = 'DESCRIBE wp_posts';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'information_schema.columns', $result );
	}

	/**
	 * @test
	 */
	public function it_handles_explain_keyword(): void {
		$sql    = 'EXPLAIN SELECT * FROM wp_posts';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'EXPLAIN', $result );
	}

	/**
	 * @test
	 */
	public function it_preserves_whitespace_in_output(): void {
		$sql    = 'SELECT id, name FROM users WHERE status = 1';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'SELECT', $result );
		$this->assertStringContainsString( 'FROM', $result );
		$this->assertStringContainsString( 'WHERE', $result );
	}

	/**
	 * @test
	 */
	public function it_handles_update_statements(): void {
		$sql    = "UPDATE `wp_options` SET `option_value` = 'new' WHERE `option_name` = 'test'";
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'UPDATE', $result );
		$this->assertStringContainsString( 'SET', $result );
		$this->assertStringContainsString( 'WHERE', $result );
	}

	/**
	 * @test
	 */
	public function it_handles_delete_statements(): void {
		$sql    = "DELETE FROM `wp_options` WHERE `option_id` = 1";
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'DELETE FROM', $result );
		$this->assertStringContainsString( 'WHERE', $result );
	}

	/**
	 * @test
	 */
	public function it_handles_join_statements(): void {
		$sql    = 'SELECT * FROM `wp_posts` p JOIN `wp_postmeta` m ON p.ID = m.post_id';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'JOIN', $result );
		$this->assertStringContainsString( 'ON', $result );
	}

	/**
	 * @test
	 */
	public function it_handles_left_join(): void {
		$sql    = 'SELECT * FROM `wp_posts` p LEFT JOIN `wp_postmeta` m ON p.ID = m.post_id';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'LEFT JOIN', $result );
	}

	/**
	 * @test
	 */
	public function it_handles_order_by(): void {
		$sql    = 'SELECT * FROM wp_posts ORDER BY post_date DESC, ID ASC';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'ORDER BY', $result );
		$this->assertStringContainsString( 'DESC', $result );
		$this->assertStringContainsString( 'ASC', $result );
	}

	/**
	 * @test
	 */
	public function it_handles_group_by(): void {
		$sql    = 'SELECT post_status, COUNT(*) as cnt FROM wp_posts GROUP BY post_status';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'GROUP BY', $result );
		$this->assertStringContainsString( 'COUNT', $result );
	}

	/**
	 * @test
	 */
	public function it_handles_having(): void {
		$sql    = 'SELECT post_status, COUNT(*) as cnt FROM wp_posts GROUP BY post_status HAVING cnt > 5';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'HAVING', $result );
	}

	/**
	 * @test
	 */
	public function it_handles_distinct(): void {
		$sql    = 'SELECT DISTINCT post_status FROM wp_posts';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'DISTINCT', $result );
	}

	/**
	 * @test
	 */
	public function it_handles_union(): void {
		$sql    = 'SELECT ID FROM wp_posts WHERE post_status = "publish" UNION SELECT ID FROM wp_posts WHERE post_status = "draft"';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'UNION', $result );
	}

	/**
	 * @test
	 */
	public function it_handles_subqueries(): void {
		$sql    = 'SELECT * FROM wp_posts WHERE ID IN (SELECT post_id FROM wp_postmeta WHERE meta_key = "_wp_old_slug")';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'SELECT', $result );
		$this->assertStringContainsString( 'IN', $result );
	}

	/**
	 * @test
	 */
	public function it_handles_case_when(): void {
		$sql    = "SELECT CASE WHEN post_status = 'publish' THEN 'Published' ELSE 'Draft' END as status FROM wp_posts";
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'CASE', $result );
		$this->assertStringContainsString( 'WHEN', $result );
		$this->assertStringContainsString( 'THEN', $result );
		$this->assertStringContainsString( 'ELSE', $result );
		$this->assertStringContainsString( 'END', $result );
	}

	/**
	 * @test
	 */
	public function it_handles_like_escape(): void {
		$sql    = "SELECT * FROM wp_posts WHERE post_title LIKE '%test%'";
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'LIKE', $result );
	}

	/**
	 * @test
	 */
	public function it_handles_between(): void {
		$sql    = 'SELECT * FROM wp_posts WHERE post_date BETWEEN "2024-01-01" AND "2024-12-31"';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'BETWEEN', $result );
		$this->assertStringContainsString( 'AND', $result );
	}

	/**
	 * @test
	 */
	public function it_handles_in_clause(): void {
		$sql    = 'SELECT * FROM wp_posts WHERE ID IN (1, 2, 3, 4, 5)';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'IN', $result );
		$this->assertStringContainsString( '1', $result );
		$this->assertStringContainsString( '5', $result );
	}

	/**
	 * @test
	 */
	public function it_handles_not_in(): void {
		$sql    = 'SELECT * FROM wp_posts WHERE ID NOT IN (1, 2, 3)';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'NOT IN', $result );
	}

	/**
	 * @test
	 */
	public function it_handles_exists(): void {
		$sql    = 'SELECT * FROM wp_posts p WHERE EXISTS (SELECT 1 FROM wp_postmeta m WHERE m.post_id = p.ID)';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'EXISTS', $result );
	}

	/**
	 * @test
	 */
	public function it_handles_not_exists(): void {
		$sql    = 'SELECT * FROM wp_posts p WHERE NOT EXISTS (SELECT 1 FROM wp_postmeta m WHERE m.post_id = p.ID)';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'NOT EXISTS', $result );
	}

	/**
	 * @test
	 */
	public function it_handles_is_null(): void {
		$sql    = 'SELECT * FROM wp_posts WHERE post_title IS NULL';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'IS NULL', $result );
	}

	/**
	 * @test
	 */
	public function it_handles_is_not_null(): void {
		$sql    = 'SELECT * FROM wp_posts WHERE post_title IS NOT NULL';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'IS NOT NULL', $result );
	}

	/**
	 * @test
	 */
	public function it_handles_count_function(): void {
		$sql    = 'SELECT COUNT(*) as total FROM wp_posts';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'COUNT', $result );
	}

	/**
	 * @test
	 */
	public function it_handles_sum_function(): void {
		$sql    = 'SELECT SUM(meta_value) as total FROM wp_postmeta';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'SUM', $result );
	}

	/**
	 * @test
	 */
	public function it_handles_avg_function(): void {
		$sql    = 'SELECT AVG(meta_value) as average FROM wp_postmeta';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'AVG', $result );
	}

	/**
	 * @test
	 */
	public function it_handles_max_function(): void {
		$sql    = 'SELECT MAX(post_date) as latest FROM wp_posts';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'MAX', $result );
	}

	/**
	 * @test
	 */
	public function it_handles_min_function(): void {
		$sql    = 'SELECT MIN(post_date) as earliest FROM wp_posts';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'MIN', $result );
	}

	/**
	 * @test
	 */
	public function it_handles_concat_function(): void {
		$sql    = "SELECT CONCAT(post_title, ' - ', post_author) as title FROM wp_posts";
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'CONCAT', $result );
	}

	/**
	 * @test
	 */
	public function it_handles_length_function(): void {
		$sql    = 'SELECT LENGTH(post_title) as len FROM wp_posts';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'LENGTH', $result );
	}

	/**
	 * @test
	 */
	public function it_handles_now_function(): void {
		$sql    = 'SELECT * FROM wp_posts WHERE post_date < NOW()';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'NOW()', $result );
	}

	/**
	 * @test
	 */
	public function it_handles_date_add_function(): void {
		$sql    = "SELECT DATE_ADD(post_date, INTERVAL 1 DAY) as next_day FROM wp_posts";
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'DATE_ADD', $result );
	}

	/**
	 * @test
	 */
	public function it_handles_aliases_with_as(): void {
		$sql    = 'SELECT ID as post_id, post_title as title FROM wp_posts';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'as', $result );
	}

	/**
	 * @test
	 */
	public function it_handles_aliases_without_as(): void {
		$sql    = 'SELECT ID post_id, post_title title FROM wp_posts';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'ID', $result );
	}

	/**
	 * @test
	 */
	public function it_handles_truncate_table(): void {
		$sql    = 'TRUNCATE TABLE wp_posts';
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'TRUNCATE', $result );
	}

	/**
	 * @test
	 */
	public function it_handles_replace_into(): void {
		$sql    = "REPLACE INTO `wp_options` (`option_name`, `option_value`) VALUES ('test', 'value')";
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'REPLACE INTO', $result );
	}

	/**
	 * @test
	 */
	public function it_handles_show_tables_like(): void {
		$sql    = "SHOW TABLES LIKE 'wp_%'";
		$result = $this->translator->translate( $sql );

		$this->assertStringContainsString( 'LIKE', $result );
		$this->assertStringContainsString( 'wp_%', $result );
	}
}
