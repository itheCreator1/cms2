<?php
/**
 * Site structure for the ΕΑΡΕΣ theme: categories, the menu pages and the
 * static front page. Run by setup.sh inside wp-cli. Idempotent: existing
 * pages and categories are left as they are (staff own their content).
 *
 * @package eares
 */

// wp-cli runs as nobody; pages and photos belong to the first Administrator.
wp_set_current_user(
	(int) get_users(
		array(
			'role'   => 'administrator',
			'fields' => 'ID',
			'number' => 1,
		)
	)[0]
);

// Category 1 ("Uncategorized") becomes Νέα, the default for new posts.
$default_cat = get_term( 1, 'category' );
if ( $default_cat && 'uncategorized' === $default_cat->slug ) {
	wp_update_term(
		1,
		'category',
		array(
			'name' => 'Νέα',
			'slug' => 'nea',
		)
	);
	WP_CLI::log( '  category: Νέα (renamed from Uncategorized)' );
}

// WordPress's own sample content.
foreach ( array( array( 'hello-world', 'post' ), array( 'sample-page', 'page' ) ) as list( $slug, $sample_type ) ) {
	$sample = get_page_by_path( $slug, OBJECT, $sample_type );
	if ( $sample ) {
		wp_delete_post( $sample->ID, true );
		WP_CLI::log( "  deleted WordPress sample $sample_type: $slug" );
	}
}

$categories = array(
	'nea'          => array( 'Νέα', '' ),
	'anakoinoseis' => array( 'Ανακοινώσεις', '' ),
	'ekdiloseis'   => array( 'Εκδηλώσεις', '' ),
	'rizareitis'   => array( 'Ο Ριζαρείτης', 'Τα φύλλα της εφημερίδας μας. Κάθε φύλλο: το πρωτοσέλιδο ως κύρια εικόνα και το PDF στο κείμενο.' ),
);
foreach ( $categories as $slug => list( $name, $description ) ) {
	if ( ! get_category_by_slug( $slug ) ) {
		wp_insert_term(
			$name,
			'category',
			array(
				'slug'        => $slug,
				'description' => $description,
			)
		);
		WP_CLI::log( "  category: $name" );
	}
}
update_option( 'default_category', get_category_by_slug( 'nea' )->term_id );

$todo = function ( $text ) {
	return "<!-- wp:paragraph {\"className\":\"eares-todo\"} -->\n<p class=\"eares-todo\"><em>[Προς συμπλήρωση: $text]</em></p>\n<!-- /wp:paragraph -->";
};

$menu_pages = array(
	'arxiki'           => array( 'Αρχή', '' ),
	'nea'              => array( 'Νέα', '' ),
	'i-enosi'          => array(
		'Η Ένωση',
		"<!-- wp:paragraph -->\n<p>Η Ένωση Αποφοίτων Ριζαρείου Εκκλησιαστικής Σχολής (Ε.Α.Ρ.Ε.Σ.) φέρνει κοντά τους αποφοίτους της Σχολής, όπου κι αν βρίσκονται.</p>\n<!-- /wp:paragraph -->\n\n"
			. $todo( 'ιστορία της Ένωσης, σκοποί, καταστατικό (PDF)' )
			. "\n\n<!-- wp:pattern {\"slug\":\"eares/divider\"} /-->\n\n<!-- wp:pattern {\"slug\":\"eares/board-grid\"} /-->",
	),
	'rizareios-scholi' => array(
		'Η Ριζάρειος Σχολή',
		"<!-- wp:paragraph -->\n<p>Η Ριζάρειος Εκκλησιαστική Σχολή ιδρύθηκε το 1844.</p>\n<!-- /wp:paragraph -->\n\n" . $todo( 'ιστορία της Σχολής, οι ιδρυτές, η Σχολή σήμερα, φωτογραφίες' ),
	),
	'syndromes'        => array(
		'Συνδρομές & εισφορές',
		"<!-- wp:paragraph -->\n<p>Η ετήσια συνδρομή στηρίζει την έκδοση του «Ριζαρείτη», τις εκδηλώσεις και το έργο της Ένωσης, και είναι προϋπόθεση για να ψηφίζετε στις εκλογές.</p>\n<!-- /wp:paragraph -->\n\n" . $todo( 'ποσό συνδρομής, τραπεζικός λογαριασμός (IBAN), αιτιολογία κατάθεσης' ),
	),
	'epikoinonia'      => array(
		'Επικοινωνία',
		$todo( 'διεύθυνση, τηλέφωνο, email, ώρες' ),
	),
);
foreach ( $menu_pages as $slug => list( $page_title, $content ) ) {
	if ( ! get_page_by_path( $slug ) ) {
		wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_name'    => $slug,
				'post_title'   => $page_title,
				'post_content' => $content,
			)
		);
		WP_CLI::log( "  page: $page_title" );
	}
}

// Header photos for pages that do not have one yet, from the theme's photos
// (copied into the Media Library so staff can swap them).
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
$page_photos = array(
	'i-enosi'          => array( 'church-night', 'Ο ναός της Ριζαρείου Σχολής τη νύχτα' ),
	'rizareios-scholi' => array( 'school', 'Το κτίριο της Ριζαρείου Εκκλησιαστικής Σχολής' ),
);
foreach ( $page_photos as $slug => list( $photo, $alt ) ) {
	$photo_page = get_page_by_path( $slug );
	$photo_file = get_theme_file_path( "assets/images/$photo.webp" );
	if ( ! $photo_page || has_post_thumbnail( $photo_page ) || ! file_exists( $photo_file ) ) {
		continue;
	}
	$photo_tmp = wp_tempnam( $photo );
	copy( $photo_file, $photo_tmp );
	$attachment = media_handle_sideload(
		array(
			'name'     => "$photo.webp",
			'tmp_name' => $photo_tmp,
		),
		$photo_page->ID,
		$alt
	);
	if ( is_wp_error( $attachment ) ) {
		WP_CLI::warning( "Photo for $slug: " . $attachment->get_error_message() );
		continue;
	}
	update_post_meta( $attachment, '_wp_attachment_image_alt', $alt );
	set_post_thumbnail( $photo_page, $attachment );
	WP_CLI::log( "  photo: $slug" );
}

update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', get_page_by_path( 'arxiki' )->ID );
update_option( 'page_for_posts', get_page_by_path( 'nea' )->ID );
update_option( 'posts_per_page', 12 );
