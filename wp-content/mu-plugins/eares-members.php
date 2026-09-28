<?php
/**
 * Plugin Name: ΕΑΡΕΣ — Members' area
 * Description: Members-only posts and pages (WordPress "Private" visibility), members-only files served through a login check, and a front-end-only experience for Members.
 *
 * Posts and pages: staff set Visibility → Private. WordPress then shows them
 * only to users with read_private_posts / read_private_pages (Members, Editors,
 * Administrators) and keeps them out of feeds, sitemaps and anonymous REST.
 *
 * Files: the "Μόνο για μέλη" checkbox on an attachment moves the file into
 * uploads/eares-members/, which Apache refuses to serve directly, and its URL
 * becomes /?eares_file=<id>, which streams it only to logged-in members.
 */

defined( 'ABSPATH' ) || exit;

const EARES_MEMBERS_DIR       = 'eares-members';
const EARES_META_MEMBERS_ONLY = '_eares_members_only';

/* -------------------------------------------------------------------------
 * Private posts: a "Μόνο για μέλη" label instead of "Ιδιωτικό:"
 * ---------------------------------------------------------------------- */

add_filter( 'private_title_format', fn() => '%s' );

add_filter(
	'render_block_core/post-title',
	function ( $content, $block, $instance ) {
		$post_id = $instance->context['postId'] ?? 0;
		if ( $post_id && 'private' === get_post_status( $post_id ) ) {
			$badge   = '<span class="eares-members-badge">' . esc_html__( 'Μόνο για μέλη', 'eares' ) . '</span>';
			$content = preg_replace( '/(<h[1-6][^>]*>)/', '$1' . $badge, $content, 1 );
		}
		return $content;
	},
	10,
	3
);

/* -------------------------------------------------------------------------
 * Members-only files
 * ---------------------------------------------------------------------- */

function eares_is_members_only_file( $attachment_id ) {
	return (bool) get_post_meta( $attachment_id, EARES_META_MEMBERS_ONLY, true );
}

/**
 * Absolute path of uploads/eares-members/, created with a deny-all
 * .htaccess on first use.
 */
function eares_members_dir() {
	$dir = trailingslashit( wp_get_upload_dir()['basedir'] ) . EARES_MEMBERS_DIR;
	if ( ! is_dir( $dir ) ) {
		wp_mkdir_p( $dir );
	}
	if ( ! file_exists( "$dir/.htaccess" ) ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- tiny config file in uploads.
		file_put_contents( "$dir/.htaccess", "# Served only through /?eares_file=<id> (eares-members.php).\nRequire all denied\n" );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( "$dir/index.php", "<?php\n// Silence.\n" );
	}
	return $dir;
}

/**
 * Move an attachment's file (and every generated size) into or out of the
 * members-only folder, keeping its year/month sub-folder.
 *
 * @return bool True when the move happened.
 */
function eares_move_attachment( $attachment_id, $to_members ) {
	$relative = (string) get_post_meta( $attachment_id, '_wp_attached_file', true );
	$prefix   = EARES_MEMBERS_DIR . '/';
	$is_in    = 0 === strpos( $relative, $prefix );
	if ( '' === $relative || $is_in === $to_members ) {
		return false;
	}

	$basedir = trailingslashit( wp_get_upload_dir()['basedir'] );
	if ( $to_members ) {
		eares_members_dir();
	}
	$new_relative = $to_members ? $prefix . $relative : substr( $relative, strlen( $prefix ) );

	$old_dir = dirname( $basedir . $relative );
	$new_dir = dirname( $basedir . $new_relative );
	wp_mkdir_p( $new_dir );

	$meta  = wp_get_attachment_metadata( $attachment_id );
	$files = array( basename( $relative ) );
	foreach ( (array) ( $meta['sizes'] ?? array() ) as $size ) {
		$files[] = $size['file'];
	}
	if ( ! empty( $meta['original_image'] ) ) {
		$files[] = $meta['original_image'];
	}
	foreach ( array_unique( $files ) as $file ) {
		if ( file_exists( "$old_dir/$file" ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename -- same filesystem.
			rename( "$old_dir/$file", "$new_dir/$file" );
		}
	}

	update_post_meta( $attachment_id, '_wp_attached_file', $new_relative );
	if ( is_array( $meta ) && isset( $meta['file'] ) ) {
		$meta['file'] = $new_relative;
		wp_update_attachment_metadata( $attachment_id, $meta );
	}
	return true;
}

/**
 * Mark an attachment members-only (or public again) and move its file.
 */
function eares_set_members_only( $attachment_id, $members_only ) {
	eares_move_attachment( $attachment_id, $members_only );
	if ( $members_only ) {
		update_post_meta( $attachment_id, EARES_META_MEMBERS_ONLY, 1 );
	} else {
		delete_post_meta( $attachment_id, EARES_META_MEMBERS_ONLY );
	}
}

add_filter(
	'attachment_fields_to_edit',
	function ( $fields, $post ) {
		if ( ! current_user_can( 'edit_post', $post->ID ) ) {
			return $fields;
		}
		$fields['eares_members_only'] = array(
			'label' => __( 'Μόνο για μέλη', 'eares' ),
			'input' => 'html',
			// The hidden field tells a save that the checkbox was on the form,
			// so an unticked box (which browsers do not send) means "public".
			'html'  => sprintf(
				'<input type="hidden" name="attachments[%1$d][eares_members_only_shown]" value="1" /><label><input type="checkbox" name="attachments[%1$d][eares_members_only]" value="1"%2$s /> %3$s</label>',
				$post->ID,
				checked( eares_is_members_only_file( $post->ID ), true, false ),
				esc_html__( 'Το αρχείο ανοίγει μόνο σε συνδεδεμένα μέλη', 'eares' )
			),
			'helps' => __( 'Για πρακτικά, καταστατικό και ό,τι άλλο δεν είναι δημόσιο.', 'eares' ),
		);
		return $fields;
	},
	10,
	2
);

add_filter(
	'attachment_fields_to_save',
	function ( $post, $attachment ) {
		if ( ! empty( $attachment['eares_members_only_shown'] ) && current_user_can( 'edit_post', $post['ID'] ) ) {
			eares_set_members_only( (int) $post['ID'], ! empty( $attachment['eares_members_only'] ) );
		}
		return $post;
	},
	10,
	2
);

/** The public address of a members-only file. */
function eares_members_file_url( $attachment_id ) {
	return add_query_arg( 'eares_file', (int) $attachment_id, home_url( '/' ) );
}

add_filter(
	'wp_get_attachment_url',
	fn( $url, $attachment_id ) => eares_is_members_only_file( $attachment_id ) ? eares_members_file_url( $attachment_id ) : $url,
	10,
	2
);

// Resized copies would be built from the filtered URL; serve the original instead.
add_filter(
	'image_downsize',
	function ( $downsize, $attachment_id ) {
		if ( ! eares_is_members_only_file( $attachment_id ) ) {
			return $downsize;
		}
		$meta = wp_get_attachment_metadata( $attachment_id );
		return array( eares_members_file_url( $attachment_id ), $meta['width'] ?? 0, $meta['height'] ?? 0, false );
	},
	10,
	2
);
add_filter(
	'wp_calculate_image_srcset',
	fn( $sources, $size, $src, $meta, $attachment_id ) => eares_is_members_only_file( $attachment_id ) ? false : $sources,
	10,
	5
);

/*
 * A File block stores the file's address when it is inserted. Rewrite it on
 * output, so ticking or unticking "Μόνο για μέλη" later never leaves a post
 * pointing at the old (now missing, or unprotected) address.
 */
add_filter(
	'render_block_core/file',
	function ( $content, $block ) {
		$attachment_id = (int) ( $block['attrs']['id'] ?? 0 );
		$old_href      = (string) ( $block['attrs']['href'] ?? '' );
		if ( ! $attachment_id || '' === $old_href || 'attachment' !== get_post_type( $attachment_id ) ) {
			return $content;
		}
		$href = wp_get_attachment_url( $attachment_id );
		if ( $href && $href !== $old_href ) {
			$content = str_replace(
				array( esc_url( $old_href ), esc_attr( $old_href ) ),
				esc_url( $href ),
				$content
			);
		}
		return $content;
	},
	10,
	2
);

// Anonymous visitors and REST clients without the capability do not see
// members-only files in the media endpoint.
add_filter(
	'rest_attachment_query',
	function ( $args ) {
		if ( ! current_user_can( 'read_private_posts' ) ) {
			$meta_query         = (array) ( $args['meta_query'] ?? array() );
			$meta_query[]       = array(
				'key'     => EARES_META_MEMBERS_ONLY,
				'compare' => 'NOT EXISTS',
			);
			$args['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}
		return $args;
	}
);

/** Stream /?eares_file=<id> to members; send everyone else to the login page. */
add_action(
	'init',
	function () {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- a plain download link.
		$attachment_id = isset( $_GET['eares_file'] ) ? absint( $_GET['eares_file'] ) : 0;
		if ( ! $attachment_id ) {
			return;
		}

		if ( 'attachment' !== get_post_type( $attachment_id ) ) {
			status_header( 404 );
			nocache_headers();
			exit;
		}

		// Public again since the link was made: send to the public address.
		if ( ! eares_is_members_only_file( $attachment_id ) ) {
			wp_safe_redirect( wp_get_attachment_url( $attachment_id ) );
			exit;
		}

		if ( ! current_user_can( 'read_private_posts' ) ) {
			wp_safe_redirect( wp_login_url( eares_members_file_url( $attachment_id ) ) );
			exit;
		}

		$path = get_attached_file( $attachment_id );
		if ( ! $path || ! is_file( $path ) ) {
			status_header( 404 );
			exit;
		}

		$type = wp_check_filetype( $path )['type'] ?: 'application/octet-stream';
		nocache_headers();
		header( 'Content-Type: ' . $type );
		header( 'Content-Length: ' . filesize( $path ) );
		header( 'Content-Disposition: inline; filename="' . rawurlencode( basename( $path ) ) . '"' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'X-Robots-Tag: noindex' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		readfile( $path );
		exit;
	},
	20 // After roles are synced and the current user is known.
);

/* -------------------------------------------------------------------------
 * Members see the site, not the dashboard
 * ---------------------------------------------------------------------- */

/** True for accounts that read but do not write (Members). */
function eares_is_reader( $user_id = 0 ) {
	$user_id = $user_id ? $user_id : get_current_user_id();
	return $user_id && ! user_can( $user_id, 'edit_posts' );
}

add_filter( 'show_admin_bar', fn( $show ) => eares_is_reader() ? false : $show );

// After logging in, members land on the home page, not on wp-admin.
add_filter(
	'login_redirect',
	function ( $redirect_to, $requested, $user ) {
		if ( $user instanceof WP_User && eares_is_reader( $user->ID )
			&& ( '' === $requested || 0 === strpos( $redirect_to, admin_url() ) ) ) {
			return home_url( '/' );
		}
		return $redirect_to;
	},
	10,
	3
);

// In wp-admin, members only have their profile.
add_action(
	'admin_init',
	function () {
		global $pagenow;
		if ( wp_doing_ajax() || ! eares_is_reader() ) {
			return;
		}
		if ( ! in_array( $pagenow, array( 'profile.php', 'admin-post.php', 'admin-ajax.php' ), true ) ) {
			wp_safe_redirect( admin_url( 'profile.php' ) );
			exit;
		}
	}
);

add_action(
	'admin_menu',
	function () {
		if ( eares_is_reader() ) {
			remove_menu_page( 'index.php' );
		}
	},
	999
);

// The toolbar preference means nothing when the toolbar is always off.
add_action(
	'admin_head-profile.php',
	function () {
		if ( eares_is_reader() ) {
			echo '<style>tr.show-admin-bar { display: none; }</style>';
		}
	}
);

// A way back to the site from the profile screen.
add_action(
	'admin_notices',
	function () {
		$screen = get_current_screen();
		if ( eares_is_reader() && $screen && 'profile' === $screen->id ) {
			printf(
				'<div class="notice notice-info"><p><a href="%s">&larr; %s</a></p></div>',
				esc_url( home_url( '/' ) ),
				esc_html__( 'Επιστροφή στον ιστότοπο', 'eares' )
			);
		}
	}
);
