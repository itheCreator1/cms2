<?php
/**
 * Plugin Name: ΕΑΡΕΣ — Hardening
 * Description: Registration off, XML-RPC off, application passwords off, user enumeration limited to logged-in users.
 *
 * File editing / plugin installs are disabled in wp-config.php
 * (DISALLOW_FILE_EDIT, DISALLOW_FILE_MODS; see docker-compose.yml).
 */

defined( 'ABSPATH' ) || exit;

// Accounts are created only by Administrators and User Managers.
add_filter( 'pre_option_users_can_register', '__return_zero' );
add_filter( 'pre_option_default_role', fn() => 'contributor' );

// XML-RPC (legacy remote publishing, frequent brute-force target).
if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
	status_header( 403 );
	exit( 'XML-RPC is disabled.' );
}
add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'xmlrpc_methods', '__return_empty_array' );
add_filter(
	'wp_headers',
	function ( $headers ) {
		unset( $headers['X-Pingback'] );
		return $headers;
	}
);

// Application passwords would bypass two-factor authentication.
add_filter( 'wp_is_application_passwords_available', '__return_false' );

// The REST users endpoint lists usernames; only logged-in users may use it.
add_filter(
	'rest_endpoints',
	function ( $endpoints ) {
		if ( ! is_user_logged_in() ) {
			foreach ( array_keys( $endpoints ) as $route ) {
				if ( 0 === strpos( $route, '/wp/v2/users' ) ) {
					unset( $endpoints[ $route ] );
				}
			}
		}
		return $endpoints;
	}
);

// ?author=N would redirect to /author/<username>/ and reveal login names.
add_action(
	'template_redirect',
	function () {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! is_user_logged_in() && isset( $_GET['author'] ) ) {
			wp_safe_redirect( home_url( '/' ), 301 );
			exit;
		}
	},
	1
);

// Hide the login names used as author archive slugs.
add_filter(
	'wp_sitemaps_add_provider',
	fn( $provider, $name ) => 'users' === $name ? false : $provider,
	10,
	2
);
