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

$admin  = (int) get_users(
	array(
		'role'   => 'administrator',
		'fields' => 'ID',
		'number' => 1,
	)
)[0];
$editor = $id_of( 'test-editor' );
$member = $id_of( 'test-member' );

// Roles: exactly Administrator, Editor, Member, Inactive.
$roles = array_keys( wp_roles()->roles );
sort( $roles );
$check( 'Roles are administrator, editor, eares_member, eares_inactive', array( 'administrator', 'eares_inactive', 'eares_member', 'editor' ) === $roles );
$check( 'Editor has no unfiltered_html', ! get_role( 'editor' )->has_cap( 'unfiltered_html' ) );
$check( 'Default role is Member', 'eares_member' === get_option( 'default_role' ) );
$check( 'Registration is open', (bool) get_option( 'users_can_register' ) );

// Members read private content and nothing more.
$check( 'Member can read private posts and pages', user_can( $member, 'read_private_posts' ) && user_can( $member, 'read_private_pages' ) );
$check( 'Member cannot write posts', ! user_can( $member, 'edit_posts' ) && ! user_can( $member, 'upload_files' ) );
$check( 'Member cannot list users', ! user_can( $member, 'list_users' ) );
$check( 'Editor cannot manage users', ! user_can( $editor, 'list_users' ) && ! user_can( $editor, 'edit_user', $member ) );
$check( 'Editor cannot reset 2FA', ! user_can( $editor, 'eares_reset_2fa', $member ) );
$check( 'Administrator can reset a Member\'s 2FA', user_can( $admin, 'eares_reset_2fa', $member ) );
$check( 'Nobody resets their own 2FA', ! user_can( $admin, 'eares_reset_2fa', $admin ) );

// Inactive accounts cannot log in.
$result = wp_authenticate( 'test-inactive', $password );
$check( 'Inactive account is refused at login', is_wp_error( $result ) && 'eares_inactive' === $result->get_error_code() );

// Members-only file from seed-content.sh.
$private = get_page_by_path( 'praktika-ds-2026-09', OBJECT, 'post' );
$check( 'Sample members-only post is private', $private && 'private' === $private->post_status );
$pdf = $private ? get_children(
	array(
		'post_parent' => $private->ID,
		'post_type'   => 'attachment',
		'fields'      => 'ids',
	)
) : array();
$pdf = $pdf ? (int) reset( $pdf ) : 0;
$check( 'Members-only PDF is flagged and moved', $pdf && eares_is_members_only_file( $pdf ) && false !== strpos( get_attached_file( $pdf ), '/eares-members/' ) );
$check( 'Members-only PDF URL goes through the login check', $pdf && false !== strpos( wp_get_attachment_url( $pdf ), 'eares_file=' . $pdf ) );

// Author archive slugs do not reveal logins.
$leaks = array_filter( get_users(), fn( $u ) => sanitize_title( $u->user_login ) === $u->user_nicename );
$check( 'No author slug equals a login name', ! $leaks );

// Personal data.
$check( 'Member-data exporter registered', isset( apply_filters( 'wp_privacy_personal_data_exporters', array() )['eares-member'] ) );
$check( 'Member-data eraser registered', isset( apply_filters( 'wp_privacy_personal_data_erasers', array() )['eares-member'] ) );

if ( $failures ) {
	WP_CLI::error( "$failures check(s) failed." );
}
