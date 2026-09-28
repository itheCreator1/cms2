<?php
/**
 * Plugin Name: ΕΑΡΕΣ — Hardening
 * Description: Self-registration only as Member, XML-RPC off, application passwords off, user enumeration limited to staff, author URLs that do not reveal login names.
 *
 * File editing / plugin installs are disabled in wp-config.php
 * (DISALLOW_FILE_EDIT, DISALLOW_FILE_MODS; see docker-compose.yml).
 */

defined( 'ABSPATH' ) || exit;

// Alumni sign up themselves (eares-registration.php) and always start as
// Members, whatever the options table says.
add_filter( 'pre_option_users_can_register', fn() => '1' );
add_filter( 'pre_option_default_role', fn() => EARES_ROLE_MEMBER );

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

// The REST users endpoint lists usernames; only staff (who pick post
// authors in the editor) may use it. Anonymous visitors and Members may not.
add_filter(
	'rest_endpoints',
	function ( $endpoints ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
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
		if ( ! current_user_can( 'edit_posts' ) && isset( $_GET['author'] ) ) {
			wp_safe_redirect( home_url( '/' ), 302 ); // Not 301: browsers would cache it for logged-in visits too.
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

/**
 * Author archive slug for a login name. WordPress derives user_nicename from
 * the login, so /author/<slug>/ links on every post would reveal it. This
 * slug is stable and unique per login but does not give the login away.
 */
function eares_member_nicename( $login ) {
	return 'member-' . substr( wp_hash( 'eares_nicename|' . $login ), 0, 10 );
}

add_filter(
	'wp_pre_insert_user_data',
	function ( $data, $update, $user_id, $userdata ) {
		// $data carries user_login only for new users; $userdata always does.
		$login = $userdata['user_login'] ?? $data['user_login'] ?? '';
		if ( '' !== $login ) {
			$data['user_nicename'] = eares_member_nicename( $login );
		}
		return $data;
	},
	10,
	4
);
