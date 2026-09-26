<?php
/**
 * Title: Υποσέλιδο
 * Slug: eares/footer
 * Categories: eares
 * Inserter: no
 *
 * @package eares
 */

?>
<!-- wp:group {"className":"eares-footer","layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group eares-footer">
	<!-- wp:columns -->
	<div class="wp-block-columns">
		<!-- wp:column {"width":"40%"} -->
		<div class="wp-block-column" style="flex-basis:40%">
			<!-- wp:heading {"level":2} -->
			<h2 class="wp-block-heading">Ε.Α.Ρ.Ε.Σ.</h2>
			<!-- /wp:heading -->
			<!-- wp:paragraph -->
			<p>Η Ένωση Αποφοίτων Ριζαρείου Εκκλησιαστικής Σχολής φέρνει κοντά τους αποφοίτους της Σχολής, όπου κι αν βρίσκονται, με εκδηλώσεις, επικοινωνία και την εφημερίδα μας, τον «Ριζαρείτη».</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->
		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:heading {"level":2} -->
			<h2 class="wp-block-heading">Σελίδες</h2>
			<!-- /wp:heading -->
			<!-- wp:list -->
			<ul class="wp-block-list">
			<?php foreach ( array_slice( eares_theme_menu_items(), 1 ) as $eares_link ) : ?>
				<!-- wp:list-item -->
				<li><a href="<?php echo esc_url( $eares_link['url'] ); ?>"><?php echo esc_html( $eares_link['label'] ); ?></a></li>
				<!-- /wp:list-item -->
			<?php endforeach; ?>
			</ul>
			<!-- /wp:list -->
		</div>
		<!-- /wp:column -->
		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:heading {"level":2} -->
			<h2 class="wp-block-heading">Μέλη</h2>
			<!-- /wp:heading -->
			<!-- wp:paragraph -->
			<p>Η ετήσια συνδρομή κρατά ζωντανή την Ένωση και τον «Ριζαρείτη».</p>
			<!-- /wp:paragraph -->
			<!-- wp:list -->
			<ul class="wp-block-list">
				<!-- wp:list-item -->
				<li><a href="<?php echo esc_url( home_url( '/syndromes/' ) ); ?>">Συνδρομές &amp; εισφορές</a></li>
				<!-- /wp:list-item -->
				<!-- wp:list-item -->
				<li><a href="<?php echo esc_url( home_url( '/epikoinonia/' ) ); ?>">Επικοινωνία</a></li>
				<!-- /wp:list-item -->
			</ul>
			<!-- /wp:list -->
			<!-- wp:loginout /-->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
	<!-- wp:group {"className":"eares-footer__bottom","layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap"}} -->
	<div class="wp-block-group eares-footer__bottom">
		<!-- wp:paragraph -->
		<p>© <?php echo esc_html( wp_date( 'Y' ) ); ?> Ένωση Αποφοίτων Ριζαρείου Εκκλησιαστικής Σχολής</p>
		<!-- /wp:paragraph -->
		<!-- wp:paragraph -->
		<p><a href="<?php echo esc_url( home_url( '/' ) ); ?>">eares.gr</a></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
