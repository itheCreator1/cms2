<?php
/**
 * Plugin Name: ΕΑΡΕΣ — Profile
 * Description: Private phone field and a simplified profile screen.
 *
 * The phone number is visible only on the profile screens, which WordPress
 * shows only to the user themself and to accounts allowed to edit them
 * (Administrators, User Managers). It is not registered for the REST API.
 */

defined( 'ABSPATH' ) || exit;

const EARES_META_PHONE = 'eares_phone';

function eares_sanitize_phone( $raw ) {
	$phone = preg_replace( '/[^0-9+\-() ]/', '', (string) $raw );
	return substr( trim( preg_replace( '/\s+/', ' ', $phone ) ), 0, 30 );
}

function eares_phone_row( $value ) {
	?>
	<tr class="eares-phone-wrap">
		<th><label for="eares_phone"><?php esc_html_e( 'Τηλέφωνο', 'eares' ); ?></label></th>
		<td>
			<input type="tel" name="eares_phone" id="eares_phone" class="regular-text" autocomplete="tel"
				value="<?php echo esc_attr( $value ); ?>" />
			<p class="description"><?php esc_html_e( 'Ιδιωτικό: το βλέπουν μόνο εσείς και η γραμματεία.', 'eares' ); ?></p>
		</td>
	</tr>
	<?php
}

/* Existing users: own profile and user-edit.php. */
function eares_profile_phone_field( WP_User $user ) {
	?>
	<h2><?php esc_html_e( 'Στοιχεία επικοινωνίας', 'eares' ); ?></h2>
	<table class="form-table" role="presentation">
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
		echo '<table class="form-table" role="presentation">';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- refill after a failed submit.
		eares_phone_row( isset( $_POST['eares_phone'] ) ? eares_sanitize_phone( wp_unslash( $_POST['eares_phone'] ) ) : '' );
		echo '</table>';
	}
);

function eares_save_phone( $user_id ) {
	// Core has already verified the nonce of the profile / new-user form.
	if ( ! current_user_can( 'edit_user', $user_id ) || ! isset( $_POST['eares_phone'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return;
	}
	$phone = eares_sanitize_phone( wp_unslash( $_POST['eares_phone'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( '' === $phone ) {
		delete_user_meta( $user_id, EARES_META_PHONE );
	} else {
		update_user_meta( $user_id, EARES_META_PHONE, $phone );
	}
}
add_action( 'personal_options_update', 'eares_save_phone' );
add_action( 'edit_user_profile_update', 'eares_save_phone' );
add_action(
	'user_register',
	function ( $user_id ) {
		if ( is_admin() && current_user_can( 'create_users' ) ) {
			eares_save_phone( $user_id );
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

/* Personal data export (Tools → Export Personal Data). */
add_filter(
	'wp_privacy_personal_data_exporters',
	function ( $exporters ) {
		$exporters['eares-phone'] = array(
			'exporter_friendly_name' => __( 'Τηλέφωνο μέλους', 'eares' ),
			'callback'               => function ( $email ) {
				$user  = get_user_by( 'email', $email );
				$phone = $user ? get_user_meta( $user->ID, EARES_META_PHONE, true ) : '';
				$data  = array();
				if ( $phone ) {
					$data[] = array(
						'group_id'    => 'user',
						'group_label' => __( 'User' ),
						'item_id'     => 'user-' . $user->ID,
						'data'        => array(
							array(
								'name'  => __( 'Τηλέφωνο', 'eares' ),
								'value' => $phone,
							),
						),
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
