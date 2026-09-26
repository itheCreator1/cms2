<?php
/**
 * ΕΑΡΕΣ theme. Almost everything lives in theme.json, templates/, parts/
 * and patterns/; this file only adds what those cannot express.
 *
 * @package eares
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'wp_enqueue_scripts',
	function () {
		$theme = wp_get_theme();
		wp_enqueue_style( 'eares-theme', get_theme_file_uri( 'assets/theme.css' ), array(), $theme->get( 'Version' ) );
	}
);

add_action(
	'after_setup_theme',
	function () {
		add_editor_style( 'assets/theme.css' );
	}
);

add_action(
	'init',
	function () {
		register_block_pattern_category( 'eares', array( 'label' => 'ΕΑΡΕΣ' ) );

		register_block_style(
			'core/separator',
			array(
				'name'  => 'ornament',
				'label' => 'Κόσμημα',
			)
		);
		register_block_style(
			'core/group',
			array(
				'name'  => 'card',
				'label' => 'Κάρτα',
			)
		);
		register_block_style(
			'core/group',
			array(
				'name'  => 'notice',
				'label' => 'Ανακοίνωση',
			)
		);
	}
);

/**
 * URL of a photo shipped with the theme (assets/images/<name>.webp or .jpg),
 * or '' when it has not been added yet, so patterns can fall back to a
 * plain background instead of a broken image.
 */
function eares_theme_image( $name ) {
	foreach ( array( 'webp', 'jpg' ) as $ext ) {
		$file = "assets/images/$name.$ext";
		if ( file_exists( get_theme_file_path( $file ) ) ) {
			return get_theme_file_uri( $file );
		}
	}
	return '';
}

/**
 * Term IDs for category slugs, for Query Loop blocks in patterns (the block
 * markup needs IDs, which differ between installs). Missing slugs are skipped.
 *
 * @param string[] $slugs Category slugs.
 * @return int[]
 */
function eares_theme_category_ids( array $slugs ) {
	$ids = array();
	foreach ( $slugs as $slug ) {
		$term = get_category_by_slug( $slug );
		if ( $term ) {
			$ids[] = (int) $term->term_id;
		}
	}
	return $ids;
}

/**
 * Categories shown as news everywhere; the Ριζαρείτης issues have their own
 * spot on the front page.
 */
function eares_theme_news_category_ids() {
	return eares_theme_category_ids( array( 'nea', 'anakoinoseis', 'ekdiloseis' ) );
}

/**
 * The main menu as navigation-link block attributes. Pages and categories are
 * linked by ID when they exist, so the current item is highlighted, and by
 * URL otherwise. scripts/setup-content.php creates them.
 *
 * @return array[]
 */
function eares_theme_menu_items() {
	$items = array(
		array( 'Αρχή', 'home', '' ),
		array( 'Η Ένωση', 'page', 'i-enosi' ),
		array( 'Νέα', 'page', 'nea' ),
		array( 'Ο Ριζαρείτης', 'category', 'rizareitis' ),
		array( 'Η Σχολή', 'page', 'rizareios-scholi' ),
		array( 'Συνδρομές', 'page', 'syndromes' ),
		array( 'Επικοινωνία', 'page', 'epikoinonia' ),
	);

	$links = array();
	foreach ( $items as list( $label, $type, $slug ) ) {
		$attrs = array(
			'label' => $label,
			'url'   => home_url( '/' ),
			'kind'  => 'custom',
		);
		if ( 'page' === $type ) {
			$page         = get_page_by_path( $slug );
			$attrs['url'] = $page ? get_permalink( $page ) : home_url( "/$slug/" );
			if ( $page ) {
				$attrs['type'] = 'page';
				$attrs['id']   = $page->ID;
				$attrs['kind'] = 'post-type';
			}
		} elseif ( 'category' === $type ) {
			$term         = get_category_by_slug( $slug );
			$attrs['url'] = $term ? get_category_link( $term ) : home_url( "/category/$slug/" );
			if ( $term ) {
				$attrs['type'] = 'category';
				$attrs['id']   = $term->term_id;
				$attrs['kind'] = 'taxonomy';
			}
		}
		$attrs['isTopLevelLink'] = true;
		$links[]                 = $attrs;
	}
	return $links;
}

// No emoji script: browsers draw emoji natively, and it would fetch images
// from s.w.org, a third-party request on every page.
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
remove_action( 'admin_print_styles', 'print_emoji_styles' );
add_filter( 'emoji_svg_url', '__return_false' );

// Visitors see "Είσοδος μελών" rather than the generic "Log in".
add_filter(
	'loginout',
	function ( $link ) {
		if ( is_user_logged_in() ) {
			return $link;
		}
		return sprintf( '<a href="%s">Είσοδος μελών</a>', esc_url( wp_login_url() ) );
	}
);
