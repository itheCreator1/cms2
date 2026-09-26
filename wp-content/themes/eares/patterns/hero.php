<?php
/**
 * Title: Κεντρική εικόνα (αρχική)
 * Slug: eares/hero
 * Categories: eares
 * Inserter: no
 *
 * The dome photo (assets/images/dome.webp|jpg) with the association's name
 * on the right, so the lit dome on the left stays in view. Without the photo, a dusk gradient stands in.
 *
 * @package eares
 */

$eares_dome = eares_theme_image( 'dome' );
?>
<?php if ( $eares_dome ) : ?>
<!-- wp:cover {"url":"<?php echo esc_url( $eares_dome ); ?>","dimRatio":50,"overlayColor":"ink","focalPoint":{"x":0.36,"y":0.45},"minHeight":72,"minHeightUnit":"vh","contentPosition":"center right","isDark":true,"align":"full","className":"eares-hero","layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-cover alignfull is-dark has-custom-content-position is-position-center-right eares-hero" style="min-height:72vh"><span aria-hidden="true" class="wp-block-cover__background has-ink-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="" src="<?php echo esc_url( $eares_dome ); ?>" style="object-position:36% 45%" data-object-fit="cover" data-object-position="36% 45%"/><div class="wp-block-cover__inner-container">
<?php else : ?>
<!-- wp:cover {"dimRatio":100,"gradient":"dusk","minHeight":60,"minHeightUnit":"vh","contentPosition":"center left","isDark":true,"align":"full","className":"eares-hero","layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-cover alignfull is-dark has-custom-content-position is-position-center-left eares-hero" style="min-height:60vh"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-100 has-background-dim has-background-gradient has-dusk-gradient-background"></span><div class="wp-block-cover__inner-container">
<?php endif; ?>
	<!-- wp:group {"className":"eares-hero__content","layout":{"type":"default"}} -->
	<div class="wp-block-group eares-hero__content">
		<!-- wp:paragraph {"className":"eares-eyebrow"} -->
		<p class="eares-eyebrow">Ε.Α.Ρ.Ε.Σ. · από το 1844</p>
		<!-- /wp:paragraph -->
		<!-- wp:heading {"level":1} -->
		<h1 class="wp-block-heading">Ένωση Αποφοίτων Ριζαρείου Εκκλησιαστικής Σχολής</h1>
		<!-- /wp:heading -->
		<!-- wp:paragraph {"className":"eares-hero__lead"} -->
		<p class="eares-hero__lead">Η Ριζάρειος μάς ένωσε. Η Ένωση μάς κρατά κοντά: νέα, εκδηλώσεις και ο «Ριζαρείτης», για όλους τους αποφοίτους.</p>
		<!-- /wp:paragraph -->
		<!-- wp:buttons -->
		<div class="wp-block-buttons">
			<!-- wp:button -->
			<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/nea/' ) ); ?>">Τα νέα μας</a></div>
			<!-- /wp:button -->
			<!-- wp:button {"className":"is-style-outline"} -->
			<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/syndromes/' ) ); ?>">Συνδρομές</a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->
</div></div>
<!-- /wp:cover -->
