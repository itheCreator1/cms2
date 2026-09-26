<?php
/**
 * FAKE sample posts for reviewing the theme. Run by seed-content.sh inside
 * wp-cli. Posts are found again by slug, so re-running adds nothing twice.
 *
 * @package eares
 */

require_once ABSPATH . 'wp-admin/includes/image.php';

$sample_cat = fn( $slug ) => get_category_by_slug( $slug )->term_id;

$lorem = 'Η Ένωση Αποφοίτων ενημερώνει τα μέλη της. Αυτό είναι δοκιμαστικό κείμενο για τον έλεγχο της εμφάνισης του ιστότοπου· θα αντικατασταθεί από πραγματικό περιεχόμενο πριν από τη δημοσίευση.';

// slug => title, category, days ago, sticky, body.
$samples = array(
	'ektaktes-ekloges-2026'  => array(
		'Έκτακτες εκλογές ΕΑΡΕΣ 2026',
		'anakoinoseis',
		1,
		true,
		"<!-- wp:paragraph -->\n<p><strong>Πρόσκληση σε Γενική Συνέλευση &amp; Εκλογές Ε.Α.Ρ.Ε.Σ.</strong><br>Η Ένωση Αποφοίτων της Ριζαρείου σας καλεί σε Γενική Συνέλευση, με σκοπό την επανάληψη των εκλογών του 2025.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>📅 <strong>Κυριακή 27/09/2026</strong><br>🕔 <strong>Ώρα 17:00</strong></p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>Παρακαλείσθε να προσέλθετε για να ψηφίσετε, έχοντας καταβάλει την ετήσια συνδρομή σας προς την Ένωση.</p>\n<!-- /wp:paragraph -->",
	),
	'thyranoixia-2026'       => array( 'Θυρανοίξια στο παρεκκλήσι της Σχολής', 'ekdiloseis', 6, false, '' ),
	'proskynima-tinos'       => array( 'Προσκύνημα της Ένωσης στην Τήνο', 'ekdiloseis', 9, false, '' ),
	'mnimosyno-evergeton'    => array( 'Μνημόσυνο των ευεργετών Μάνθου και Γεωργίου Ριζάρη', 'ekdiloseis', 14, false, '' ),
	'nea-mela-ds'            => array( 'Συγκρότηση σε σώμα του νέου Διοικητικού Συμβουλίου', 'anakoinoseis', 20, false, '' ),
	'ypotrofies-2026'        => array( 'Υποτροφίες της Ένωσης για το σχολικό έτος 2026–27', 'nea', 26, false, '' ),
	'episkepsi-mathiton'     => array( 'Επίσκεψη μαθητών της Σχολής στο Άγιον Όρος', 'nea', 33, false, '' ),
	'syndromes-2026'         => array( 'Υπενθύμιση: συνδρομές 2026', 'anakoinoseis', 40, false, '' ),
	'polytoniko-keimeno'     => array( 'Ἡ ἑορτὴ τῶν Τριῶν Ἱεραρχῶν', 'nea', 48, false, "<!-- wp:paragraph -->\n<p>Ἐν ἀρχῇ ἦν ὁ Λόγος, καὶ ὁ Λόγος ἦν πρὸς τὸν Θεόν. Δοκιμαστικό πολυτονικό κείμενο για τον έλεγχο των γραμματοσειρών.</p>\n<!-- /wp:paragraph -->" ),
	'synantisi-apofoiton-80' => array( 'Συνάντηση των αποφοίτων της δεκαετίας του ’80', 'nea', 60, false, '' ),
	'rizareitis-fyllo-132'   => array( 'Ο Ριζαρείτης — Φύλλο 132', 'rizareitis', 12, false, '' ),
	'rizareitis-fyllo-131'   => array( 'Ο Ριζαρείτης — Φύλλο 131', 'rizareitis', 100, false, '' ),
);

/**
 * A plain "newspaper front page" placeholder for sample issues, so the
 * Ριζαρείτης cards have a cover. Drawn with GD; no fonts needed.
 */
$make_cover = function ( $post_id, $number ) {
	$img   = imagecreatetruecolor( 600, 800 );
	$paper = imagecolorallocate( $img, 247, 241, 230 );
	$red   = imagecolorallocate( $img, 122, 30, 35 );
	$grey  = imagecolorallocate( $img, 190, 180, 165 );
	$photo = imagecolorallocate( $img, 200, 170, 120 );
	imagefill( $img, 0, 0, $paper );
	imagefilledrectangle( $img, 30, 30, 570, 130, $red );
	imagefilledrectangle( $img, 30, 145, 570, 150, $red );
	imagefilledrectangle( $img, 30, 175, 380, 430, $photo );
	// Text lines: beside the photo, then full width below it.
	for ( $y = 180; $y < 760; $y += 22 ) {
		imagefilledrectangle( $img, $y < 440 ? 400 : 30, $y, 570, $y + 8, $grey );
	}
	$file = get_temp_dir() . "rizareitis-$number.png";
	imagepng( $img, $file );
	$attachment = media_handle_sideload(
		array(
			'name'     => "rizareitis-$number.png",
			'tmp_name' => $file,
		),
		$post_id,
		"Πρωτοσέλιδο φύλλου $number (δοκιμή)"
	);
	if ( ! is_wp_error( $attachment ) ) {
		set_post_thumbnail( $post_id, $attachment );
	}
};

require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';

foreach ( $samples as $slug => list( $post_title, $category, $days_ago, $sticky, $body ) ) {
	if ( get_page_by_path( $slug, OBJECT, 'post' ) ) {
		continue;
	}
	$sample_id = wp_insert_post(
		array(
			'post_type'     => 'post',
			'post_status'   => 'publish',
			'post_name'     => $slug,
			'post_title'    => $post_title,
			'post_content'  => $body ? $body : "<!-- wp:paragraph -->\n<p>$lorem</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>$lorem</p>\n<!-- /wp:paragraph -->",
			'post_date'     => wp_date( 'Y-m-d H:i:s', time() - $days_ago * DAY_IN_SECONDS ),
			'post_category' => array( $sample_cat( $category ) ),
		)
	);
	if ( $sticky ) {
		stick_post( $sample_id );
	}
	if ( 'rizareitis' === $category ) {
		$make_cover( $sample_id, substr( $slug, -3 ) );
	}
	WP_CLI::log( "  post: $post_title" );
}
