<?php
/**
 * Plugin Name: ΕΑΡΕΣ — Roles
 * Description: Defines the "User Manager" and "Inactive" roles, adjusts the core roles and stops non-administrators from touching administrator or User Manager accounts.
 *
 * Roles are stored in the database by WordPress, so this file is the source of
 * truth: whenever EARES_ROLES_VERSION changes the roles are rebuilt from the
 * definitions below. Bump the version after editing capabilities.
 */

defined( 'ABSPATH' ) || exit;

const EARES_ROLES_VERSION = '1';
const EARES_ROLE_USER_MANAGER = 'eares_user_manager';
const EARES_ROLE_INACTIVE = 'eares_inactive';

/** Custom capability: may clear another user's two-factor settings. */
const EARES_CAP_RESET_2FA = 'eares_reset_2fa';

/**
 * Roles that only an Administrator may see, edit or assign.
 *
 * @return string[]
 */
function eares_protected_roles() {
	return array( 'administrator', EARES_ROLE_USER_MANAGER );
}

/**
 * True when $user_id may manage every account, including administrators.
 */
function eares_is_full_admin( $user_id ) {
	return user_can( $user_id, 'manage_options' );
}

/**
 * True when the user holds a protected role.
 */
function eares_user_is_protected( $user_id ) {
	$user = get_userdata( (int) $user_id );
	return $user && array_intersect( eares_protected_roles(), (array) $user->roles );
}

/**
 * Rebuild custom roles and core-role adjustments when the definitions change.
 */
function eares_sync_roles() {
	if ( get_option( 'eares_roles_version' ) === EARES_ROLES_VERSION ) {
		return;
	}

	$editor = get_role( 'editor' );
	if ( ! $editor ) {
		return; // WordPress is not installed yet.
	}

	remove_role( EARES_ROLE_USER_MANAGER );
	add_role(
		EARES_ROLE_USER_MANAGER,
		'User Manager',
		array_merge(
			$editor->capabilities,
			array(
				'list_users'        => true,
				'create_users'      => true,
				'edit_users'        => true,
				'delete_users'      => true,
				'promote_users'     => true,
				EARES_CAP_RESET_2FA => true,
			)
		)
	);

	remove_role( EARES_ROLE_INACTIVE );
	add_role( EARES_ROLE_INACTIVE, 'Inactive', array() );

	get_role( 'administrator' )->add_cap( EARES_CAP_RESET_2FA );

	// Contributors may attach photos to the drafts they submit for approval.
	get_role( 'contributor' )->add_cap( 'upload_files' );

	update_option( 'eares_roles_version', EARES_ROLES_VERSION );
}
add_action( 'init', 'eares_sync_roles', 1 );

/**
 * Show the custom role names in Greek (the site default) or English,
 * following the viewing user's profile language.
 */
add_filter(
	'gettext_with_context',
	function ( $translation, $text, $context ) {
		if ( 'User role' !== $context ) {
			return $translation;
		}
		$greek = array(
			'User Manager' => 'Διαχειριστής Χρηστών',
			'Inactive'     => 'Ανενεργός',
		);
		if ( isset( $greek[ $text ] ) && 0 === strpos( determine_locale(), 'el' ) ) {
			return $greek[ $text ];
		}
		return $translation;
	},
	10,
	3
);

/**
 * Non-administrators can never pick Administrator or User Manager in any
 * role dropdown (new user, edit user, bulk "change role to"). WordPress
 * validates submitted roles against this list, so this also blocks forged
 * requests.
 */
add_filter(
	'editable_roles',
	function ( $roles ) {
		if ( ! eares_is_full_admin( get_current_user_id() ) ) {
			foreach ( eares_protected_roles() as $role ) {
				unset( $roles[ $role ] );
			}
		}
		return $roles;
	}
);

/**
 * Non-administrators cannot edit, delete, promote or reset 2FA for an
 * account holding a protected role (editing one's own profile stays allowed).
 */
add_filter(
	'map_meta_cap',
	function ( $caps, $cap, $user_id, $args ) {
		$guarded = array( 'edit_user', 'delete_user', 'promote_user', 'remove_user', EARES_CAP_RESET_2FA );
		if ( ! in_array( $cap, $guarded, true ) || empty( $args[0] ) ) {
			return $caps;
		}

		$target = (int) $args[0];

		if ( EARES_CAP_RESET_2FA === $cap && $target === (int) $user_id ) {
			// Users manage their own 2FA from their profile.
			return array( 'do_not_allow' );
		}

		if ( 'edit_user' === $cap && $target === (int) $user_id ) {
			return $caps;
		}

		if ( eares_user_is_protected( $target ) && ! eares_is_full_admin( $user_id ) ) {
			return array( 'do_not_allow' );
		}

		return $caps;
	},
	10,
	4
);

/**
 * Hide protected accounts from non-administrators: the Users screen, its
 * role counters and the REST users endpoint.
 */
function eares_hide_protected_users_from_current_user() {
	return is_user_logged_in()
		&& current_user_can( 'list_users' )
		&& ! eares_is_full_admin( get_current_user_id() );
}

add_action(
	'pre_get_users',
	function ( WP_User_Query $query ) {
		global $pagenow;
		if ( is_admin() && 'users.php' === $pagenow && eares_hide_protected_users_from_current_user() ) {
			$query->set( 'role__not_in', array_merge( (array) $query->get( 'role__not_in' ), eares_protected_roles() ) );
		}
	}
);

add_filter(
	'rest_user_query',
	function ( $args ) {
		if ( eares_hide_protected_users_from_current_user() ) {
			$args['role__not_in'] = array_merge( (array) ( $args['role__not_in'] ?? array() ), eares_protected_roles() );
		}
		return $args;
	}
);

add_filter(
	'views_users',
	function ( $views ) {
		if ( eares_hide_protected_users_from_current_user() ) {
			$counts = count_users();
			$hidden = 0;
			foreach ( eares_protected_roles() as $role ) {
				unset( $views[ $role ] );
				$hidden += $counts['avail_roles'][ $role ] ?? 0;
			}
			// "All (N)" must not reveal how many accounts are hidden.
			if ( isset( $views['all'] ) ) {
				$views['all'] = preg_replace(
					'/<span class="count">\([^)]*\)<\/span>/',
					'<span class="count">(' . number_format_i18n( $counts['total_users'] - $hidden ) . ')</span>',
					$views['all']
				);
			}
		}
		return $views;
	}
);
