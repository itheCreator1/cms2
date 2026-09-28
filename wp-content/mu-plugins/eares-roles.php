<?php
/**
 * Plugin Name: ΕΑΡΕΣ — Roles
 * Description: Two roles: Administrator (the staff) and Member (alumni who read members-only content). Removes the core roles nobody uses.
 *
 * Roles are stored in the database by WordPress, so this file is the source of
 * truth: whenever EARES_ROLES_VERSION changes the roles are rebuilt from the
 * definitions below. Bump the version after editing capabilities.
 *
 *   Administrator  the 2-5 staff: write, publish, manage accounts
 *   Member         alumni: read private (members-only) posts and pages
 *   (no role)      accounts left without a role cannot log in
 *                  (eares-users-admin.php); unwanted accounts are deleted
 */

defined( 'ABSPATH' ) || exit;

const EARES_ROLES_VERSION = '4';
const EARES_ROLE_MEMBER   = 'eares_member';

/** Custom capability: may clear another user's two-factor settings. */
const EARES_CAP_RESET_2FA = 'eares_reset_2fa';

/**
 * Roles removed by this plugin, and where their users go. Staff roles
 * become Administrator; reader roles become Member; Inactive accounts are
 * left without a role, which keeps them locked out.
 *
 * @return array<string, string> old role => new role ('' for none)
 */
function eares_retired_roles() {
	return array(
		'editor'             => 'administrator',
		'eares_user_manager' => 'administrator',
		'author'             => EARES_ROLE_MEMBER,
		'contributor'        => EARES_ROLE_MEMBER,
		'subscriber'         => EARES_ROLE_MEMBER,
		'eares_inactive'     => '',
	);
}

/**
 * Rebuild custom roles and core-role adjustments when the definitions change.
 */
function eares_sync_roles() {
	if ( get_option( 'eares_roles_version' ) === EARES_ROLES_VERSION ) {
		return;
	}

	$administrator = get_role( 'administrator' );
	if ( ! $administrator ) {
		return; // WordPress is not installed yet.
	}

	remove_role( EARES_ROLE_MEMBER );
	add_role(
		EARES_ROLE_MEMBER,
		'Member',
		array(
			'read'               => true,
			'read_private_posts' => true,
			'read_private_pages' => true,
		)
	);

	$administrator->add_cap( EARES_CAP_RESET_2FA );

	// Move people off the retired roles before removing them.
	foreach ( eares_retired_roles() as $old => $new ) {
		foreach ( get_users(
			array(
				'role'   => $old,
				'fields' => 'ID',
			)
		) as $user_id ) {
			$user = new WP_User( $user_id );
			$user->remove_role( $old );
			if ( ! $user->roles && '' !== $new ) {
				$user->add_role( $new );
			}
		}
		remove_role( $old );
	}

	update_option( 'eares_roles_version', EARES_ROLES_VERSION );
}
add_action( 'init', 'eares_sync_roles', 1 );

/**
 * Show the Member role name in Greek (the site default) or English,
 * following the viewing user's profile language.
 */
add_filter(
	'gettext_with_context',
	function ( $translation, $text, $context ) {
		if ( 'User role' !== $context ) {
			return $translation;
		}
		if ( 'Member' === $text && 0 === strpos( determine_locale(), 'el' ) ) {
			return 'Μέλος';
		}
		return $translation;
	},
	10,
	3
);

/** Users manage their own 2FA from their profile, never through a reset. */
add_filter(
	'map_meta_cap',
	function ( $caps, $cap, $user_id, $args ) {
		if ( EARES_CAP_RESET_2FA === $cap && isset( $args[0] ) && (int) $args[0] === (int) $user_id ) {
			return array( 'do_not_allow' );
		}
		return $caps;
	},
	10,
	4
);
