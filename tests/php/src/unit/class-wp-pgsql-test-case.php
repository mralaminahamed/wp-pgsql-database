<?php
/**
 * Base test case for all WP PostgreSQL Database unit tests.
 *
 * @package WP_PgSQL_Database\Tests
 */

declare( strict_types=1 );

namespace WP_PgSQL_Database\Tests\unit;

use Brain\Monkey;
use PHPUnit\Framework\TestCase;
use function Brain\Monkey;

/**
 * Class WP_PgSQL_Test_Case
 *
 * Extends PHPUnit's TestCase with Brain\Monkey setup/teardown and
 * common helper assertions used across the test suite.
 */
abstract class WP_PgSQL_Test_Case extends TestCase {

	/**
	 * Set up Brain\Monkey before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	/**
	 * Tear down Brain\Monkey after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}
}
