<?php
/**
 * Creates or refreshes one FAKE test user per role. Run by seed-users.sh
 * inside a single wp-cli container, password in EARES_PASSWORD.
 *
 * @package eares
 */

$password = (string) getenv( 'EARES_PASSWORD' );
if ( '' === $password ) {
	WP_CLI::error( 'Set EARES_PASSWORD (see seed-users.sh).' );
}

// login => email, role ('' for none), display name, phone.
$users = array(
	'test-staff'  => array( 'staff@eares.local', 'administrator', 'Μέλος ΔΣ (δοκιμή)', '210 000 0002' ),
	'test-member' => array( 'member@eares.local', 'eares_member', 'Απόφοιτος Μέλος (δοκιμή)', '210 000 0003' ),
	'test-norole' => array( 'norole@eares.local', '', 'Χωρίς ρόλο (δοκιμή)', '210 000 0005' ),
);

foreach ( $users as $login => list( $email, $user_role, $name, $phone ) ) {
	$existing = get_user_by( 'login', $login );
	$data     = array(
		'user_login'   => $login,
		'user_email'   => $email,
		'role'         => $user_role,
		'display_name' => $name,
		'user_pass'    => $password,
	);
	if ( $existing ) {
		$data['ID'] = $existing->ID;
		add_filter( 'send_password_change_email', '__return_false' );
		$user_id = wp_update_user( $data );
	} else {
		$user_id = wp_insert_user( $data );
	}
	if ( is_wp_error( $user_id ) ) {
		WP_CLI::error( "$login: " . $user_id->get_error_message() );
	}
	// Known state for smoke-test.sh: no two-factor on test accounts.
	foreach ( eares_two_factor_meta_keys() as $key ) {
		delete_user_meta( $user_id, $key );
	}
	update_user_meta( $user_id, 'eares_phone', $phone );
	update_user_meta( $user_id, 'eares_grad_year', '1998' );
	WP_CLI::log( "  $login ($user_role) id=$user_id" );
}

// Test accounts of roles that no longer exist.
require_once ABSPATH . 'wp-admin/includes/user.php';
foreach ( array( 'test-usermanager', 'test-author', 'test-contributor', 'test-editor', 'test-inactive' ) as $retired ) {
	$old = get_user_by( 'login', $retired );
	if ( $old ) {
		wp_delete_user( $old->ID );
		WP_CLI::log( "  removed $retired" );
	}
}
