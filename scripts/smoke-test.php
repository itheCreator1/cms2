<?php
/**
 * WordPress-side checks for smoke-test.sh. Needs seed-users.sh data.
 * Run inside wp-cli, seed password in EARES_PASSWORD.
 *
 * @package eares
 */

$password = (string) getenv( 'EARES_PASSWORD' );
$failures = 0;

$check = function ( $label, $ok ) use ( &$failures ) {
	WP_CLI::log( ( $ok ? '  ok    ' : '  FAIL  ' ) . $label );
	if ( ! $ok ) {
		++$failures;
	}
};

$id_of = function ( $login ) {
	$user = get_user_by( 'login', $login );
	if ( ! $user ) {
		WP_CLI::error( "Missing user $login: run scripts/seed-users.sh first." );
	}
	return $user->ID;
};

$admin   = (int) get_users(
	array(
		'role'   => 'administrator',
		'fields' => 'ID',
		'number' => 1,
	)
)[0];
$manager = $id_of( 'test-usermanager' );
$editor  = $id_of( 'test-editor' );

// Roles.
$check( 'User Manager and Inactive roles exist', get_role( 'eares_user_manager' ) && get_role( 'eares_inactive' ) );
$check( 'Editor has no unfiltered_html', ! get_role( 'editor' )->has_cap( 'unfiltered_html' ) );
$check( 'User Manager has no unfiltered_html', ! get_role( 'eares_user_manager' )->has_cap( 'unfiltered_html' ) );
$check( 'User Manager cannot edit an Administrator', ! user_can( $manager, 'edit_user', $admin ) );
$check( 'User Manager cannot reset an Administrator\'s 2FA', ! user_can( $manager, 'eares_reset_2fa', $admin ) );
$check( 'User Manager can edit an Editor', user_can( $manager, 'edit_user', $editor ) );
$check( 'User Manager can reset an Editor\'s 2FA', user_can( $manager, 'eares_reset_2fa', $editor ) );
wp_set_current_user( $manager );
$check( 'User Manager cannot assign Administrator', ! isset( get_editable_roles()['administrator'] ) );
wp_set_current_user( 0 );

// Inactive accounts cannot log in.
$result = wp_authenticate( 'test-inactive', $password );
$check( 'Inactive account is refused at login', is_wp_error( $result ) && 'eares_inactive' === $result->get_error_code() );

// Email changed by someone else: only an Administrator may reset 2FA for a week.
$old_email = get_userdata( $editor )->user_email;
wp_set_current_user( $manager );
wp_update_user(
	array(
		'ID'         => $editor,
		'user_email' => 'changed-' . $old_email,
	)
);
$check( 'User Manager cannot reset 2FA right after changing the email', ! user_can( $manager, 'eares_reset_2fa', $editor ) );
$check( 'Administrator still can', user_can( $admin, 'eares_reset_2fa', $editor ) );
wp_update_user(
	array(
		'ID'         => $editor,
		'user_email' => $old_email,
	)
);
delete_user_meta( $editor, 'eares_email_changed_at' );
wp_set_current_user( 0 );

// Author archive slugs do not reveal logins.
$leaks = array_filter( get_users(), fn( $u ) => sanitize_title( $u->user_login ) === $u->user_nicename );
$check( 'No author slug equals a login name', ! $leaks );

// Personal data.
$check( 'Phone exporter registered', isset( apply_filters( 'wp_privacy_personal_data_exporters', array() )['eares-phone'] ) );
$check( 'Phone eraser registered', isset( apply_filters( 'wp_privacy_personal_data_erasers', array() )['eares-phone'] ) );

// Activity log: a User Manager sees nothing about Administrators.
if ( class_exists( '\Simple_History\Log_Query' ) ) {
	wp_set_current_user( $manager );
	$rows = ( new \Simple_History\Log_Query() )->query( array( 'posts_per_page' => 1000 ) )['log_rows'];
	$seen = array_filter(
		$rows,
		function ( $row ) use ( $admin ) {
			$context = (array) $row->context;
			return in_array( (string) $admin, array( $context['_user_id'] ?? '', $context['edited_user_id'] ?? '' ), true );
		}
	);
	$check( 'User Manager sees no Administrator events in the log', ! $seen );
	wp_set_current_user( 0 );
}

if ( $failures ) {
	WP_CLI::error( "$failures check(s) failed." );
}
