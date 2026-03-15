<?php
/**
 * Query logger — captures and stores translated queries in WP_DEBUG mode.
 *
 * @package WP_PgSQL_Database\Compat
 * @since   1.0.0
 */

declare( strict_types=1 );

namespace WP_PgSQL_Database\Compat;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WP_PgSQL_Query_Logger
 *
 * Hooks into the query pipeline to record translated queries, execution
 * times, and caller traces. Only active when both WP_DEBUG and
 * WP_DEBUG_LOG are true, or when explicitly enabled via the plugin admin.
 */
class WP_PgSQL_Query_Logger {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * In-memory query log for the current request.
	 *
	 * @var array<int, array{sql: string, translated: string, duration: float, caller: string}>
	 */
	private array $log = [];

	/**
	 * Maximum number of entries to retain in-memory.
	 *
	 * @var int
	 */
	private const MAX_ENTRIES = 500;

	/**
	 * Private constructor.
	 */
	private function __construct() {
		add_filter( 'query', [ $this, 'capture_query_start' ], 1 );
		add_action( 'shutdown', [ $this, 'flush_to_log_file' ] );
	}

	/**
	 * Return the singleton instance.
	 *
	 * @return self
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Capture a query passing through the `query` filter.
	 *
	 * @param string $sql Incoming SQL string.
	 * @return string Unmodified SQL (this filter must be transparent).
	 */
	public function capture_query_start( string $sql ): string {
		if ( count( $this->log ) >= self::MAX_ENTRIES ) {
			return $sql;
		}

		$this->log[] = [
			'sql'        => $sql,
			'translated' => '',
			'duration'   => microtime( true ),
			'caller'     => $this->get_caller(),
		];

		return $sql;
	}

	/**
	 * Update the last log entry with its duration after execution.
	 *
	 * @param string $translated Translated SQL string.
	 * @return void
	 */
	public function capture_query_end( string $translated ): void {
		if ( empty( $this->log ) ) {
			return;
		}

		$last              = &$this->log[ count( $this->log ) - 1 ];
		$last['translated'] = $translated;
		$last['duration']   = microtime( true ) - $last['duration'];
	}

	/**
	 * Flush the in-memory log to the WordPress debug log file on shutdown.
	 *
	 * @return void
	 */
	public function flush_to_log_file(): void {
		if ( empty( $this->log ) || ! defined( 'WP_DEBUG_LOG' ) || ! WP_DEBUG_LOG ) {
			return;
		}

		$total  = count( $this->log );
		$time   = round( array_sum( array_column( $this->log, 'duration' ) ) * 1000, 2 );
		$header = sprintf( "[WP PgSQL] %d queries / %.2fms\n", $total, $time );

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( $header );
	}

	/**
	 * Return the entire in-memory query log.
	 *
	 * @return array<int, array{sql: string, translated: string, duration: float, caller: string}>
	 */
	public function get_log(): array {
		return $this->log;
	}

	/**
	 * Determine the PHP caller responsible for triggering the query.
	 *
	 * @return string
	 */
	private function get_caller(): string {
		$trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 6 ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace

		foreach ( $trace as $frame ) {
			$file = $frame['file'] ?? '';
			if ( str_contains( $file, 'wp-pgsql-database' ) || str_contains( $file, 'class-wpdb.php' ) ) {
				continue;
			}

			return sprintf(
				'%s:%d',
				wp_basename( $file ),
				$frame['line'] ?? 0
			);
		}

		return 'unknown';
	}

	/**
	 * Prevent cloning.
	 *
	 * @return void
	 */
	private function __clone() {}
}
