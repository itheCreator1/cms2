<?php
/**
 * Plugin Name: ΕΑΡΕΣ — Users admin
 * Description: Last-login tracking, 2FA and Last-login columns on the Users screen, a "Reset 2FA" action, two-factor provider selection and a login block for accounts without a role.
 */

defined( 'ABSPATH' ) || exit;

const EARES_META_LAST_LOGIN = 'eares_last_login';

/** Accounts without a login for this long are flagged on the Users screen. */
const EARES_STALE_AFTER = YEAR_IN_SECONDS;

/* -------------------------------------------------------------------------
 * Two-Factor plugin configuration
 * ---------------------------------------------------------------------- */

/**
 * Offer only the methods explained in the members' guide:
 * authenticator app, backup codes and emailed codes.
 */
add_filter(
	'two_factor_providers',
	function ( $providers ) {
		return array_intersect_key(
			$providers,
			array_flip( array( 'Two_Factor_Totp', 'Two_Factor_Backup_Codes', 'Two_Factor_Email' ) )
		);
	},
	20
);

function eares_user_has_2fa( $user_id ) {
	return class_exists( 'Two_Factor_Core' ) && Two_Factor_Core::is_user_using_two_factor( $user_id );
}

/**
 * Every user-meta key the Two-Factor plugin keeps per user.
 *
 * @return string[]
 */
function eares_two_factor_meta_keys() {
	$keys = array(
		'_two_factor_provider',
		'_two_factor_enabled_providers',
		'_two_factor_nonce',
		'_two_factor_last_login_failure',
		'_two_factor_failed_login_attempts',
		'_two_factor_password_was_reset',
		'_two_factor_totp_key',
		'_two_factor_totp_last_successful_login',
		'_two_factor_backup_codes',
		'_two_factor_email_token',
		'_two_factor_email_token_timestamp',
	);

	// Pick up keys added by future plugin versions.
	foreach ( array( 'Two_Factor_Totp', 'Two_Factor_Backup_Codes', 'Two_Factor_Email' ) as $class ) {
		if ( class_exists( $class ) && method_exists( $class, 'uninstall_user_meta_keys' ) ) {
			$keys = array_merge( $keys, (array) $class::uninstall_user_meta_keys() );
		}
	}

	return array_unique( $keys );
}

/* -------------------------------------------------------------------------
 * Accounts without a role: no login
 * ---------------------------------------------------------------------- */

/**
 * WordPress lets a user with no role on the site log in to an empty
 * dashboard. Here they are refused, so "No role for this site" is a safe
 * way to lock someone out while deciding whether to delete the account.
 *
 * Runs after the username/email/application password checks (priority 20)
 * and before Two-Factor (31), so such an account never gets a session.
 */
add_filter(
	'authenticate',
	function ( $user ) {
		if ( $user instanceof WP_User && ! $user->roles ) {
			return new WP_Error(
				'eares_no_role',
				__( '<strong>Σφάλμα:</strong> Ο λογαριασμός σας δεν είναι ενεργός. Επικοινωνήστε με τον διαχειριστή του ιστότοπου.', 'eares' )
			);
		}
		return $user;
	},
	25
);

/** Log out an account everywhere as soon as it loses its last role. */
add_action(
	'set_user_role',
	function ( $user_id, $role ) {
		if ( '' === (string) $role ) {
			WP_Session_Tokens::get_instance( $user_id )->destroy_all();
		}
	},
	10,
	2
);

/* -------------------------------------------------------------------------
 * Last login
 * ---------------------------------------------------------------------- */

function eares_record_login( $user_id ) {
	update_user_meta( $user_id, EARES_META_LAST_LOGIN, time() );
}

/**
 * wp_login fires after the password check, before the second factor. For
 * 2FA users wait until the code has been accepted.
 */
add_action(
	'wp_login',
	function ( $user_login, $user ) {
		if ( ! eares_user_has_2fa( $user->ID ) ) {
			eares_record_login( $user->ID );
		}
	},
	10,
	2
);

add_action(
	'two_factor_user_authenticated',
	function ( $user ) {
		eares_record_login( $user->ID );
	}
);

function eares_is_stale( WP_User $user ) {
	$last  = (int) get_user_meta( $user->ID, EARES_META_LAST_LOGIN, true );
	$since = $last ? $last : strtotime( $user->user_registered . ' UTC' );
	return $since && ( time() - $since ) > EARES_STALE_AFTER;
}

/* -------------------------------------------------------------------------
 * Users screen columns
 * ---------------------------------------------------------------------- */

add_filter(
	'manage_users_columns',
	function ( $columns ) {
		unset( $columns['two-factor'] ); // Replaced by our sortable column.
		$columns['eares_2fa']        = __( '2FA', 'eares' );
		$columns['eares_last_login'] = __( 'Τελευταία σύνδεση', 'eares' );
		return $columns;
	},
	20
);

add_filter(
	'manage_users_sortable_columns',
	function ( $columns ) {
		$columns['eares_2fa']        = array( 'eares_2fa', false );
		$columns['eares_last_login'] = array( 'eares_last_login', true );
		return $columns;
	}
);

add_filter(
	'manage_users_custom_column',
	function ( $output, $column, $user_id ) {
		if ( 'eares_2fa' === $column ) {
			return eares_user_has_2fa( $user_id )
				? '<span style="color:#008a20;font-weight:600">✔ ' . esc_html__( 'Ενεργό', 'eares' ) . '</span>'
				: '<span style="color:#646970">— ' . esc_html__( 'Όχι', 'eares' ) . '</span>';
		}

		if ( 'eares_last_login' === $column ) {
			$user = get_userdata( $user_id );
			$last = (int) get_user_meta( $user_id, EARES_META_LAST_LOGIN, true );
			$html = $last
				? esc_html( wp_date( get_option( 'date_format' ), $last ) )
				: esc_html__( 'Ποτέ', 'eares' );
			if ( $user && eares_is_stale( $user ) ) {
				$html .= ' <span style="display:inline-block;padding:0 6px;border-radius:3px;background:#d63638;color:#fff;font-size:11px">'
					. esc_html__( '12+ μήνες', 'eares' ) . '</span>';
			}
			return $html;
		}

		return $output;
	},
	10,
	3
);

/**
 * Sorting and the "12+ months" filter. The OR / NOT EXISTS clauses keep
 * users that never logged in (or never set up 2FA) in the list.
 */
add_action(
	'pre_get_users',
	function ( WP_User_Query $query ) {
		global $pagenow;
		if ( ! is_admin() || 'users.php' !== $pagenow ) {
			return;
		}

		$orderby = $query->get( 'orderby' );
		$meta    = array(
			'eares_last_login' => EARES_META_LAST_LOGIN,
			'eares_2fa'        => '_two_factor_enabled_providers',
		);
		if ( is_string( $orderby ) && isset( $meta[ $orderby ] ) ) {
			$meta_query   = (array) $query->get( 'meta_query' );
			$meta_query[] = array(
				'relation'   => 'OR',
				'eares_sort' => array(
					'key'     => $meta[ $orderby ],
					'compare' => 'EXISTS',
					'type'    => 'eares_last_login' === $orderby ? 'NUMERIC' : 'CHAR',
				),
				array(
					'key'     => $meta[ $orderby ],
					'compare' => 'NOT EXISTS',
				),
			);
			$query->set( 'meta_query', $meta_query );
			$query->set( 'orderby', 'eares_sort' );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only list filter.
		if ( ! empty( $_GET['eares_stale'] ) ) {
			$cutoff       = time() - EARES_STALE_AFTER;
			$meta_query   = (array) $query->get( 'meta_query' );
			$meta_query[] = array(
				'relation' => 'OR',
				array(
					'key'     => EARES_META_LAST_LOGIN,
					'value'   => $cutoff,
					'compare' => '<',
					'type'    => 'NUMERIC',
				),
				array(
					'key'     => EARES_META_LAST_LOGIN,
					'compare' => 'NOT EXISTS',
				),
			);
			$query->set( 'meta_query', $meta_query );
			// Never-logged-in accounts only count once they are a year old.
			$restrict = function ( WP_User_Query $q ) use ( $cutoff, &$restrict ) {
				remove_action( 'pre_user_query', $restrict ); // This query only.
				global $wpdb;
				$q->query_where .= $wpdb->prepare(
					" AND ( {$wpdb->users}.user_registered < %s OR EXISTS ( SELECT 1 FROM {$wpdb->usermeta} ll WHERE ll.user_id = {$wpdb->users}.ID AND ll.meta_key = %s ) )",
					gmdate( 'Y-m-d H:i:s', $cutoff ),
					EARES_META_LAST_LOGIN
				);
			};
			add_action( 'pre_user_query', $restrict );
		}
	}
);

add_filter(
	'views_users',
	function ( $views ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current              = ! empty( $_GET['eares_stale'] );
		$views['eares_stale'] = sprintf(
			'<a href="%s"%s>%s</a>',
			esc_url( add_query_arg( 'eares_stale', 1, admin_url( 'users.php' ) ) ),
			$current ? ' class="current" aria-current="page"' : '',
			esc_html__( 'Χωρίς σύνδεση 12+ μήνες', 'eares' )
		);
		return $views;
	},
	20
);

/* -------------------------------------------------------------------------
 * Reset 2FA
 * ---------------------------------------------------------------------- */

add_filter(
	'user_row_actions',
	function ( $actions, $user ) {
		if ( current_user_can( EARES_CAP_RESET_2FA, $user->ID ) && eares_user_has_2fa( $user->ID ) ) {
			$url = wp_nonce_url(
				add_query_arg(
					array(
						'action'  => 'eares_reset_2fa',
						'user_id' => $user->ID,
					),
					admin_url( 'admin-post.php' )
				),
				'eares_reset_2fa_' . $user->ID
			);
			/* translators: %s: user display name */
			$confirm = esc_js( sprintf( __( 'Επαναφορά 2FA για τον χρήστη %s; Θα μπορεί να συνδεθεί μόνο με τον κωδικό του μέχρι να ενεργοποιήσει ξανά το 2FA.', 'eares' ), $user->display_name ) );

			$actions['eares_reset_2fa'] = sprintf(
				'<a href="%s" style="color:#b32d2e" onclick="return confirm(\'%s\')">%s</a>',
				esc_url( $url ),
				$confirm,
				esc_html__( 'Επαναφορά 2FA', 'eares' )
			);
		}
		return $actions;
	},
	10,
	2
);

add_action(
	'admin_post_eares_reset_2fa',
	function () {
		$user_id = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- the nonce names the user; checked next.
		check_admin_referer( 'eares_reset_2fa_' . $user_id );

		$user = get_userdata( $user_id );
		if ( ! $user || ! current_user_can( EARES_CAP_RESET_2FA, $user_id ) ) {
			wp_die( esc_html__( 'Δεν έχετε δικαίωμα για αυτή την ενέργεια.', 'eares' ), 403 );
		}

		foreach ( eares_two_factor_meta_keys() as $key ) {
			delete_user_meta( $user_id, $key );
		}

		$actor = wp_get_current_user();

		do_action(
			'simple_history_log', // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			'Reset two-factor authentication for user "{reset_user_login}"',
			array(
				'reset_user_id'    => $user_id,
				'reset_user_login' => $user->user_login,
				'reset_user_email' => $user->user_email,
			),
			'notice'
		);

		// Tell the account owner, so an unexpected reset does not go unnoticed.
		wp_mail(
			$user->user_email,
			sprintf( '[%s] %s', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), __( 'Επαναφορά επαλήθευσης δύο βημάτων', 'eares' ) ),
			sprintf(
				/* translators: 1: user display name, 2: who reset it, 3: login URL */
				__( "Γεια σας %1\$s,\n\nΗ επαλήθευση δύο βημάτων (2FA) του λογαριασμού σας απενεργοποιήθηκε από τον/την %2\$s.\nΜπορείτε τώρα να συνδεθείτε μόνο με τον κωδικό σας και να την ενεργοποιήσετε ξανά από το προφίλ σας.\n\nΑν δεν το ζητήσατε εσείς, επικοινωνήστε αμέσως με τον διαχειριστή του ιστότοπου.\n\n%3\$s\n", 'eares' ),
				$user->display_name,
				$actor->display_name,
				wp_login_url()
			)
		);

		wp_safe_redirect( add_query_arg( 'eares_2fa_reset', $user_id, admin_url( 'users.php' ) ) );
		exit;
	}
);

add_action(
	'admin_notices',
	function () {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
		$user_id = isset( $_GET['eares_2fa_reset'] ) ? absint( $_GET['eares_2fa_reset'] ) : 0;
		$user    = $user_id ? get_userdata( $user_id ) : null;
		if ( $user && 'users' === get_current_screen()->id ) {
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				/* translators: %s: user display name */
				esc_html( sprintf( __( 'Έγινε επαναφορά του 2FA για τον χρήστη %s. Του στάλθηκε ενημερωτικό email.', 'eares' ), $user->display_name ) )
			);
		}
	}
);

/* -------------------------------------------------------------------------
 * Activity log visibility
 * ---------------------------------------------------------------------- */

/** Only Administrators see the Simple History log. */
add_filter(
	'simple_history/view_history_capability',
	function () {
		return 'manage_options';
	}
);
