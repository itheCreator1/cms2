<?php
/**
 * Plugin Name: ΕΑΡΕΣ — Registration
 * Description: Alumni sign up themselves on wp-login.php?action=register and become Members at once. Extra fields (name, graduation year, phone), a honeypot and a per-IP limit.
 *
 * There is no approval step: WordPress emails the new member a link to set
 * their password (which confirms the address) and tells the Administrator.
 * The role is always Member (eares-hardening.php forces default_role, and
 * user_register below re-applies it), so a forged form cannot pick another.
 */

defined( 'ABSPATH' ) || exit;

/** Sign-ups allowed per IP address per window. */
const EARES_SIGNUP_MAX_PER_IP = 3;
const EARES_SIGNUP_WINDOW     = HOUR_IN_SECONDS;

/** Name of the honeypot field: invisible to people, tempting to bots. */
const EARES_SIGNUP_HONEYPOT = 'eares_website';

function eares_signup_key() {
	return 'eares_su_ip_' . md5( eares_client_ip() );
}

/**
 * The submitted value of a sign-up field, unslashed and trimmed.
 */
function eares_signup_field( $name ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- wp-login.php has no nonce for registration; each caller sanitises.
	return isset( $_POST[ $name ] ) ? trim( (string) wp_unslash( $_POST[ $name ] ) ) : '';
}

add_action(
	'register_form',
	function () {
		$fields = array(
			'first_name'      => array( __( 'Όνομα', 'eares' ), 'text', 'given-name', true ),
			'last_name'       => array( __( 'Επώνυμο', 'eares' ), 'text', 'family-name', true ),
			'eares_grad_year' => array( __( 'Έτος αποφοίτησης', 'eares' ), 'number', 'off', true ),
			'eares_phone'     => array( __( 'Τηλέφωνο (προαιρετικό)', 'eares' ), 'tel', 'tel', false ),
		);
		foreach ( $fields as $name => list( $label, $type, $autocomplete, $required ) ) {
			printf(
				'<p><label for="%1$s">%2$s</label><input type="%3$s" name="%1$s" id="%1$s" class="input" value="%4$s" autocomplete="%5$s"%6$s%7$s /></p>',
				esc_attr( $name ),
				esc_html( $label ),
				esc_attr( $type ),
				esc_attr( sanitize_text_field( eares_signup_field( $name ) ) ),
				esc_attr( $autocomplete ),
				$required ? ' required' : '',
				'number' === $type ? ' min="' . esc_attr( EARES_FIRST_GRAD_YEAR ) . '" max="' . esc_attr( wp_date( 'Y' ) ) . '"' : ''
			);
		}
		// Off-screen rather than display:none, which some bots detect.
		printf(
			'<p style="position:absolute;left:-9999px" aria-hidden="true"><label for="%1$s">Website</label><input type="text" name="%1$s" id="%1$s" tabindex="-1" autocomplete="off" value="" /></p>',
			esc_attr( EARES_SIGNUP_HONEYPOT )
		);
		echo '<p class="description" style="margin-bottom:16px">'
			. esc_html__( 'Το τηλέφωνο και το έτος αποφοίτησης τα βλέπουν μόνο εσείς και ο διαχειριστής του ιστότοπου.', 'eares' )
			. '</p>';
	}
);

add_filter(
	'registration_errors',
	function ( WP_Error $errors ) {
		if ( '' !== eares_signup_field( EARES_SIGNUP_HONEYPOT ) ) {
			// Say nothing specific to the bot.
			$errors->add( 'eares_signup_rejected', __( '<strong>Σφάλμα:</strong> Η εγγραφή δεν ολοκληρώθηκε. Δοκιμάστε ξανά.', 'eares' ) );
			return $errors;
		}

		$entry = get_transient( eares_signup_key() );
		if ( is_array( $entry ) && $entry['count'] >= EARES_SIGNUP_MAX_PER_IP ) {
			$errors->add(
				'eares_signup_throttled',
				sprintf(
					/* translators: %d: minutes */
					__( '<strong>Σφάλμα:</strong> Πάρα πολλές εγγραφές από αυτή τη σύνδεση. Δοκιμάστε ξανά σε %d λεπτά.', 'eares' ),
					(int) ceil( max( 60, $entry['until'] - time() ) / MINUTE_IN_SECONDS )
				)
			);
			return $errors;
		}

		if ( '' === sanitize_text_field( eares_signup_field( 'first_name' ) ) ) {
			$errors->add( 'eares_first_name', __( '<strong>Σφάλμα:</strong> Συμπληρώστε το όνομά σας.', 'eares' ) );
		}
		if ( '' === sanitize_text_field( eares_signup_field( 'last_name' ) ) ) {
			$errors->add( 'eares_last_name', __( '<strong>Σφάλμα:</strong> Συμπληρώστε το επώνυμό σας.', 'eares' ) );
		}
		if ( '' === eares_sanitize_grad_year( eares_signup_field( 'eares_grad_year' ) ) ) {
			$errors->add(
				'eares_grad_year',
				sprintf(
					/* translators: 1: earliest year, 2: current year */
					__( '<strong>Σφάλμα:</strong> Γράψτε το έτος αποφοίτησης (από %1$d έως %2$d).', 'eares' ),
					EARES_FIRST_GRAD_YEAR,
					(int) wp_date( 'Y' )
				)
			);
		}
		return $errors;
	}
);

/**
 * Runs only for the public sign-up form (register_new_user fires
 * register_new_user after creating the account; admins adding users on
 * user-new.php never reach it).
 */
add_action(
	'register_new_user',
	function ( $user_id ) {
		$user = new WP_User( $user_id );
		$user->set_role( EARES_ROLE_MEMBER );

		$first = sanitize_text_field( eares_signup_field( 'first_name' ) );
		$last  = sanitize_text_field( eares_signup_field( 'last_name' ) );
		wp_update_user(
			array(
				'ID'           => $user_id,
				'first_name'   => $first,
				'last_name'    => $last,
				// The public name is the person's, never their login.
				'display_name' => trim( "$first $last" ),
				'nickname'     => trim( "$first $last" ),
			)
		);
		eares_update_private_meta( $user_id, EARES_META_GRAD_YEAR, eares_sanitize_grad_year( eares_signup_field( 'eares_grad_year' ) ) );
		eares_update_private_meta( $user_id, EARES_META_PHONE, eares_sanitize_phone( eares_signup_field( 'eares_phone' ) ) );

		$key   = eares_signup_key();
		$entry = get_transient( $key );
		if ( ! is_array( $entry ) ) {
			$entry = array(
				'count' => 0,
				'until' => time() + EARES_SIGNUP_WINDOW,
			);
		}
		++$entry['count'];
		set_transient( $key, $entry, max( 1, $entry['until'] - time() ) );

		do_action(
			'simple_history_log', // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			'New member "{member_name}" signed up (class of {grad_year}) from IP {ip}',
			array(
				'member_name' => trim( "$first $last" ),
				'grad_year'   => get_user_meta( $user_id, EARES_META_GRAD_YEAR, true ),
				'ip'          => eares_client_ip(),
			),
			'info'
		);
	},
	5 // Before core sends the emails (priority 10), so they use the real name.
);

/*
 * The Administrators' "new registration" email carries what they need to
 * judge whether the sign-up is a real alumnus: name, graduation year, phone.
 * (register_new_user above has stored them by the time core sends it.)
 */
add_filter(
	'wp_new_user_notification_email_admin',
	function ( $email, $user ) {
		$lines = array(
			__( 'Νέα εγγραφή μέλους στον ιστότοπο της ΕΑΡΕΣ.', 'eares' ),
			'',
			/* translators: %s: full name */
			sprintf( __( 'Ονοματεπώνυμο: %s', 'eares' ), $user->display_name ),
			/* translators: %s: graduation year */
			sprintf( __( 'Έτος αποφοίτησης: %s', 'eares' ), get_user_meta( $user->ID, EARES_META_GRAD_YEAR, true ) ),
			/* translators: %s: phone number or a dash */
			sprintf( __( 'Τηλέφωνο: %s', 'eares' ), get_user_meta( $user->ID, EARES_META_PHONE, true ) ?: '—' ),
			/* translators: %s: email address */
			sprintf( __( 'Email: %s', 'eares' ), $user->user_email ),
			/* translators: %s: login name */
			sprintf( __( 'Όνομα χρήστη: %s', 'eares' ), $user->user_login ),
			'',
			__( 'Αν δεν πρόκειται για απόφοιτο, διαγράψτε τον λογαριασμό:', 'eares' ),
			admin_url( 'user-edit.php?user_id=' . $user->ID ),
		);

		$email['message'] = implode( "\r\n", $lines ) . "\r\n";
		return $email;
	},
	10,
	2
);

/* Wording of the sign-up screen. */
add_filter(
	'login_message',
	function ( $message ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
		$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';
		if ( 'register' === $action ) {
			return '<p class="message">'
				. esc_html__( 'Εγγραφή μέλους: για αποφοίτους της Ριζαρείου Εκκλησιαστικής Σχολής. Μετά την εγγραφή θα λάβετε email για να ορίσετε κωδικό.', 'eares' )
				. '</p>';
		}
		return $message;
	}
);
