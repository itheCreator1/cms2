<?php
/**
 * Plugin Name: ΕΑΡΕΣ — Profile
 * Description: Private phone and graduation-year fields and a simplified profile screen.
 *
 * Both are visible only on the profile screens, which WordPress shows only to
 * the user themself and to Administrators. They are not registered for the
 * REST API. Members fill them in when they sign up (eares-registration.php).
 */

defined( 'ABSPATH' ) || exit;

const EARES_META_PHONE     = 'eares_phone';
const EARES_META_GRAD_YEAR = 'eares_grad_year';

/** The school's first graduating class could not be earlier than its founding. */
const EARES_FIRST_GRAD_YEAR = 1844;

function eares_sanitize_phone( $raw ) {
	$phone = preg_replace( '/[^0-9+\-() ]/', '', (string) $raw );
	return substr( trim( preg_replace( '/\s+/', ' ', $phone ) ), 0, 30 );
}

/**
 * A plausible graduation year as a string, or '' if the input is not one.
 */
function eares_sanitize_grad_year( $raw ) {
	$year = absint( $raw );
	return ( $year >= EARES_FIRST_GRAD_YEAR && $year <= (int) wp_date( 'Y' ) ) ? (string) $year : '';
}

function eares_grad_year_row( $value ) {
	?>
	<tr class="eares-grad-year-wrap">
		<th><label for="eares_grad_year"><?php esc_html_e( 'Έτος αποφοίτησης', 'eares' ); ?></label></th>
		<td>
			<input type="number" name="eares_grad_year" id="eares_grad_year" class="small-text"
				min="<?php echo esc_attr( EARES_FIRST_GRAD_YEAR ); ?>" max="<?php echo esc_attr( wp_date( 'Y' ) ); ?>"
				value="<?php echo esc_attr( $value ); ?>" />
		</td>
	</tr>
	<?php
}

function eares_phone_row( $value ) {
	?>
	<tr class="eares-phone-wrap">
		<th><label for="eares_phone"><?php esc_html_e( 'Τηλέφωνο', 'eares' ); ?></label></th>
		<td>
			<input type="tel" name="eares_phone" id="eares_phone" class="regular-text" autocomplete="tel"
				value="<?php echo esc_attr( $value ); ?>" />
			<p class="description"><?php esc_html_e( 'Ιδιωτικό: το βλέπουν μόνο εσείς και ο διαχειριστής του ιστότοπου.', 'eares' ); ?></p>
		</td>
	</tr>
	<?php
}

/* Existing users: own profile and user-edit.php. */
function eares_profile_phone_field( WP_User $user ) {
	?>
	<h2><?php esc_html_e( 'Στοιχεία μέλους', 'eares' ); ?></h2>
	<table class="form-table" role="presentation">
		<?php eares_grad_year_row( get_user_meta( $user->ID, EARES_META_GRAD_YEAR, true ) ); ?>
		<?php eares_phone_row( get_user_meta( $user->ID, EARES_META_PHONE, true ) ); ?>
	</table>
	<?php
}
add_action( 'show_user_profile', 'eares_profile_phone_field', 5 );
add_action( 'edit_user_profile', 'eares_profile_phone_field', 5 );

/* New user form (Users → Add New). */
add_action(
	'user_new_form',
	function ( $context ) {
		if ( 'add-new-user' !== $context ) {
			return;
		}
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- refill after a failed submit.
		echo '<table class="form-table" role="presentation">';
		eares_grad_year_row( isset( $_POST['eares_grad_year'] ) ? eares_sanitize_grad_year( wp_unslash( $_POST['eares_grad_year'] ) ) : '' );
		eares_phone_row( isset( $_POST['eares_phone'] ) ? eares_sanitize_phone( wp_unslash( $_POST['eares_phone'] ) ) : '' );
		echo '</table>';
		// phpcs:enable
	}
);

/**
 * Store (or clear) a sanitised private field.
 */
function eares_update_private_meta( $user_id, $key, $value ) {
	if ( '' === $value ) {
		delete_user_meta( $user_id, $key );
	} else {
		update_user_meta( $user_id, $key, $value );
	}
}

function eares_save_member_fields( $user_id ) {
	// Core has already verified the nonce of the profile / new-user form.
	// phpcs:disable WordPress.Security.NonceVerification.Missing
	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return;
	}
	if ( isset( $_POST['eares_phone'] ) ) {
		eares_update_private_meta( $user_id, EARES_META_PHONE, eares_sanitize_phone( wp_unslash( $_POST['eares_phone'] ) ) );
	}
	if ( isset( $_POST['eares_grad_year'] ) ) {
		eares_update_private_meta( $user_id, EARES_META_GRAD_YEAR, eares_sanitize_grad_year( wp_unslash( $_POST['eares_grad_year'] ) ) );
	}
	// phpcs:enable
}
add_action( 'personal_options_update', 'eares_save_member_fields' );
add_action( 'edit_user_profile_update', 'eares_save_member_fields' );
add_action(
	'user_register',
	function ( $user_id ) {
		if ( is_admin() && current_user_can( 'create_users' ) ) {
			eares_save_member_fields( $user_id );
		}
	}
);

/* -------------------------------------------------------------------------
 * Simplified profile screen
 * ---------------------------------------------------------------------- */

// Admin colour scheme picker (core registers it after mu-plugins load).
add_action(
	'admin_init',
	function () {
		remove_action( 'admin_color_scheme_picker', 'admin_color_scheme_picker' );
	}
);

// Website and biography rows have no hook of their own; hide them.
add_action(
	'admin_head',
	function () {
		$screen = get_current_screen();
		if ( $screen && in_array( $screen->id, array( 'profile', 'user-edit', 'user' ), true ) ) {
			echo '<style>tr.user-url-wrap, tr.user-description-wrap, tr:has(#url) { display: none; }</style>';
		}
	}
);

/* Personal data export and erasure (Tools → Export / Erase Personal Data). */
add_filter(
	'wp_privacy_personal_data_exporters',
	function ( $exporters ) {
		$exporters['eares-member'] = array(
			'exporter_friendly_name' => __( 'Στοιχεία μέλους', 'eares' ),
			'callback'               => function ( $email ) {
				$user   = get_user_by( 'email', $email );
				$fields = array(
					EARES_META_GRAD_YEAR => __( 'Έτος αποφοίτησης', 'eares' ),
					EARES_META_PHONE     => __( 'Τηλέφωνο', 'eares' ),
				);
				$rows   = array();
				foreach ( $user ? $fields : array() as $key => $label ) {
					$value = get_user_meta( $user->ID, $key, true );
					if ( '' !== $value ) {
						$rows[] = array(
							'name'  => $label,
							'value' => $value,
						);
					}
				}
				$data = array();
				if ( $rows ) {
					$data[] = array(
						'group_id'    => 'user',
						'group_label' => __( 'User', 'default' ),
						'item_id'     => 'user-' . $user->ID,
						'data'        => $rows,
					);
				}
				return array(
					'data' => $data,
					'done' => true,
				);
			},
		);
		return $exporters;
	}
);

add_filter(
	'wp_privacy_personal_data_erasers',
	function ( $erasers ) {
		$erasers['eares-member'] = array(
			'eraser_friendly_name' => __( 'Στοιχεία μέλους', 'eares' ),
			'callback'             => function ( $email ) {
				$user    = get_user_by( 'email', $email );
				$removed = false;
				foreach ( $user ? array( EARES_META_PHONE, EARES_META_GRAD_YEAR ) : array() as $key ) {
					if ( '' !== get_user_meta( $user->ID, $key, true ) && delete_user_meta( $user->ID, $key ) ) {
						$removed = true;
					}
				}
				return array(
					'items_removed'  => $removed,
					'items_retained' => false,
					'messages'       => array(),
					'done'           => true,
				);
			},
		);
		return $erasers;
	}
);
