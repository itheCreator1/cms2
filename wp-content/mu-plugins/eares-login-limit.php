<?php
/**
 * Plugin Name: ΕΑΡΕΣ — Login limit
 * Description: Locks out password guessing: 5 failed logins for one username from one IP, or 20 from one IP, block that IP for 15 minutes.
 *
 * Behind a reverse proxy set EARES_TRUST_PROXY=1 (see .env.example), or every
 * visitor shares the proxy's IP and one attacker locks everybody out.
 */

defined( 'ABSPATH' ) || exit;

const EARES_LOGIN_WINDOW       = 15 * MINUTE_IN_SECONDS;
const EARES_LOGIN_MAX_PER_USER = 5;
const EARES_LOGIN_MAX_PER_IP   = 20;

/**
 * The visitor's IP. With EARES_TRUST_PROXY=1 this is the last X-Forwarded-For
 * entry, the one our own proxy appended; earlier entries come from the client
 * and can be forged.
 */
function eares_client_ip() {
	$ip = wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- validated below.
	if ( '1' === getenv( 'EARES_TRUST_PROXY' ) && ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		$forwarded = explode( ',', wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- validated below.
		$ip        = trim( end( $forwarded ) );
	}
	return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '0.0.0.0';
}

/**
 * Transient keys for the per-username and per-IP counters.
 *
 * @return array{user: string, ip: string}
 */
function eares_login_limit_keys( $username ) {
	$ip = eares_client_ip();
	return array(
		'user' => 'eares_ll_u_' . md5( $ip . '|' . strtolower( trim( (string) $username ) ) ),
		'ip'   => 'eares_ll_ip_' . md5( $ip ),
	);
}

/**
 * Seconds until the lockout for this username / IP ends, or 0.
 */
function eares_login_locked_for( $username ) {
	$keys = eares_login_limit_keys( $username );
	$max  = array(
		'user' => EARES_LOGIN_MAX_PER_USER,
		'ip'   => EARES_LOGIN_MAX_PER_IP,
	);
	$wait = 0;
	foreach ( $keys as $kind => $key ) {
		$entry = get_transient( $key );
		if ( is_array( $entry ) && $entry['count'] >= $max[ $kind ] ) {
			$wait = max( $wait, $entry['until'] - time() );
		}
	}
	return max( 0, $wait );
}

/**
 * Runs after every other check (core 20, no-role block 25, Two-Factor 31): core's
 * password check would overwrite an earlier error, and a locked-out visitor
 * must be refused even with the right password.
 */
add_filter(
	'authenticate',
	function ( $user, $username ) {
		$wait = eares_login_locked_for( $username );
		if ( ! $wait ) {
			return $user;
		}
		return new WP_Error(
			'eares_locked_out',
			sprintf(
				/* translators: %d: minutes */
				__( '<strong>Σφάλμα:</strong> Πάρα πολλές αποτυχημένες προσπάθειες σύνδεσης. Δοκιμάστε ξανά σε %d λεπτά.', 'eares' ),
				(int) ceil( $wait / MINUTE_IN_SECONDS )
			)
		);
	},
	99,
	2
);

add_action(
	'wp_login_failed',
	function ( $username, $error = null ) {
		// Attempts during a lockout neither count nor extend it.
		if ( $error instanceof WP_Error && 'eares_locked_out' === $error->get_error_code() ) {
			return;
		}

		$max = array(
			'user' => EARES_LOGIN_MAX_PER_USER,
			'ip'   => EARES_LOGIN_MAX_PER_IP,
		);
		foreach ( eares_login_limit_keys( $username ) as $kind => $key ) {
			$entry = get_transient( $key );
			if ( ! is_array( $entry ) ) {
				$entry = array(
					'count' => 0,
					'until' => time() + EARES_LOGIN_WINDOW,
				);
			}
			++$entry['count'];
			set_transient( $key, $entry, max( 1, $entry['until'] - time() ) );

			if ( $entry['count'] === $max[ $kind ] ) {
				do_action(
					'simple_history_log', // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
					'Login locked out for {minutes} minutes after {count} failed attempts from IP {ip} (username "{failed_username}")',
					array(
						'minutes'         => EARES_LOGIN_WINDOW / MINUTE_IN_SECONDS,
						'count'           => $entry['count'],
						'ip'              => eares_client_ip(),
						'failed_username' => $username,
					),
					'warning'
				);
			}
		}
	},
	10,
	2
);

/** A successful login clears that username's counter (not the IP's). */
add_action(
	'wp_login',
	function ( $user_login ) {
		delete_transient( eares_login_limit_keys( $user_login )['user'] );
	}
);
