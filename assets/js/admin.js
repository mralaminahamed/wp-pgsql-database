/**
 * WP PostgreSQL Database — Admin JavaScript
 *
 * @package WP_PgSQL_Database
 * @since   1.0.0
 */

/* global wpPgsqlDatabase, jQuery */

( function ( $, config ) {
	'use strict';

	/**
	 * Test the PostgreSQL connection via AJAX and display inline feedback.
	 */
	function testConnection() {
		const $btn    = $( '#wp-pgsql-test-connection' );
		const $status = $( '#wp-pgsql-connection-status' );

		if ( ! $btn.length ) {
			return;
		}

		$btn.on( 'click', function ( e ) {
			e.preventDefault();

			$btn.prop( 'disabled', true ).text( config.i18n.saving );
			$status.text( '' ).removeClass( 'notice-success notice-error' );

			$.post(
				config.ajaxUrl,
				{
					action : 'wp_pgsql_test_connection',
					_wpnonce: config.nonce,
				},
				function ( response ) {
					if ( response.success ) {
						$status.addClass( 'notice notice-success' ).text( config.i18n.connectionOk );
					} else {
						$status.addClass( 'notice notice-error' ).text( config.i18n.connectionFailed );
					}
				}
			).always( function () {
				$btn.prop( 'disabled', false ).text( 'Test Connection' );
			} );
		} );
	}

	/**
	 * Confirm destructive actions (drop-in removal).
	 */
	function confirmRemoveDropin() {
		$( document ).on( 'submit', 'form[action*="wp_pgsql_remove_dropin"]', function () {
			return window.confirm(
				'Remove the db.php drop-in? WordPress will revert to the default MySQL driver until you reinstall it.'
			);
		} );
	}

	$( function () {
		testConnection();
		confirmRemoveDropin();
	} );

}( jQuery, window.wpPgsqlDatabase || {} ) );
