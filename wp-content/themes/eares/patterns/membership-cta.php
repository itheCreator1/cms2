<?php
/**
 * Title: Πρόσκληση για συνδρομή
 * Slug: eares/membership-cta
 * Categories: eares
 * Description: Πράσινη λωρίδα με σύνδεσμο προς τις συνδρομές.
 *
 * @package eares
 */

?>
<!-- wp:group {"align":"full","className":"eares-band","layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group alignfull eares-band">
	<!-- wp:group {"layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap","verticalAlignment":"center"}} -->
	<div class="wp-block-group">
		<!-- wp:group {"style":{"layout":{"selfStretch":"fixed","flexSize":"36rem"}},"layout":{"type":"default"}} -->
		<div class="wp-block-group">
			<!-- wp:heading {"level":2} -->
			<h2 class="wp-block-heading">Η Ένωση είμαστε όλοι εμείς</h2>
			<!-- /wp:heading -->
			<!-- wp:paragraph -->
			<p>Η ετήσια συνδρομή στηρίζει την έκδοση του «Ριζαρείτη», τις εκδηλώσεις και το έργο της Ένωσης. Είναι και προϋπόθεση για να ψηφίζετε στις εκλογές.</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
		<!-- wp:buttons -->
		<div class="wp-block-buttons">
			<!-- wp:button -->
			<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/syndromes/' ) ); ?>">Συνδρομές &amp; εισφορές</a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
