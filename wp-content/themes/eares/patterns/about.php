<?php
/**
 * Title: Η Ένωση (αρχική)
 * Slug: eares/about
 * Categories: eares
 * Inserter: no
 *
 * The welcome text of the old eares.gr, with the church porch photo
 * (assets/images/portico.webp|jpg) beside it when present.
 *
 * @package eares
 */

$eares_portico = eares_theme_image( 'portico' );
$eares_years   = (int) wp_date( 'Y' ) - 1844;
$eares_about   = get_page_by_path( 'i-enosi' );
$eares_about   = $eares_about ? get_permalink( $eares_about ) : home_url( '/i-enosi/' );
?>
<!-- wp:group {"align":"full","className":"eares-about","layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group alignfull eares-about">
	<!-- wp:columns {"verticalAlignment":"center","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|60"}}}} -->
	<div class="wp-block-columns are-vertically-aligned-center">
		<?php if ( $eares_portico ) : ?>
		<!-- wp:column {"verticalAlignment":"center","width":"45%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:45%">
			<!-- wp:image {"aspectRatio":"4/3","scale":"cover"} -->
			<figure class="wp-block-image"><img src="<?php echo esc_url( $eares_portico ); ?>" alt="Ο ναός της Ριζαρείου Σχολής με τη στοά του" style="aspect-ratio:4/3;object-fit:cover"/></figure>
			<!-- /wp:image -->
		</div>
		<!-- /wp:column -->
		<?php endif; ?>
		<!-- wp:column {"verticalAlignment":"center"} -->
		<div class="wp-block-column is-vertically-aligned-center">
			<!-- wp:paragraph {"className":"eares-eyebrow"} -->
			<p class="eares-eyebrow">Καλώς ήρθατε</p>
			<!-- /wp:paragraph -->
			<!-- wp:heading {"level":2} -->
			<h2 class="wp-block-heading">Η Γεραρά Ριζάρειος Σχολή</h2>
			<!-- /wp:heading -->
			<!-- wp:paragraph {"className":"eares-about__quote"} -->
			<p class="eares-about__quote">Η Τροφός και Μάνα μας συμπληρώνει αισίως <?php echo esc_html( $eares_years ); ?> χρόνια ζωής. Οι απόφοιτοί της πληθαίνουν και σκορπίζονται ανά την Ελλάδα και τον κόσμο. Εύλογα και φυσικά στρέφονται συχνά προς τη Σχολή μας και προς την Ένωσή μας.</p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph -->
			<p>Η δεύτερη πασχίζει να φέρει κοντά τα μέλη της, όλους εμάς δηλαδή. Όλους τους αποφοίτους, με επιστολές, προσκλήσεις, τηλεφωνήματα και με την εφημερίδα μας, τον «Ριζαρείτη».</p>
			<!-- /wp:paragraph -->
			<!-- wp:buttons -->
			<div class="wp-block-buttons">
				<!-- wp:button -->
				<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( $eares_about ); ?>">Περισσότερα για την Ένωση</a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
