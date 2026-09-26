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

// login => email, role, display name, phone.
$users = array(
	'test-usermanager' => array( 'usermanager@eares.local', 'eares_user_manager', 'Γραμματεία (δοκιμή)', '210 000 0001' ),
	'test-editor'      => array( 'editor@eares.local', 'editor', 'Μέλος ΔΣ (δοκιμή)', '210 000 0002' ),
	'test-author'      => array( 'author@eares.local', 'author', 'Απόφοιτος Author (δοκιμή)', '210 000 0003' ),
	'test-contributor' => array( 'contributor@eares.local', 'contributor', 'Απόφοιτος Contributor (δοκιμή)', '210 000 0004' ),
	'test-inactive'    => array( 'inactive@eares.local', 'eares_inactive', 'Πρώην μέλος ΔΣ (δοκιμή)', '210 000 0005' ),
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
	update_user_meta( $user_id, 'eares_phone', $phone );
	WP_CLI::log( "  $login ($user_role) id=$user_id" );
}

// A post by the inactive user, to check it stays published.
$inactive = get_user_by( 'login', 'test-inactive' );
if ( ! get_posts(
	array(
		'author'      => $inactive->ID,
		'post_status' => 'any',
		'fields'      => 'ids',
	)
) ) {
	wp_insert_post(
		array(
			'post_author'  => $inactive->ID,
			'post_status'  => 'publish',
			'post_title'   => 'Δοκιμαστική ανακοίνωση πρώην μέλους ΔΣ',
			'post_content' => 'Αυτό το άρθρο πρέπει να παραμένει δημοσιευμένο ενώ ο λογαριασμός είναι ανενεργός.',
		)
	);
}
