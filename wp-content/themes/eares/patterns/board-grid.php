<?php
/**
 * Title: Διοικητικό Συμβούλιο
 * Slug: eares/board-grid
 * Categories: eares
 * Description: Κάρτες με όνομα και θέση για τα μέλη του ΔΣ.
 *
 * @package eares
 */

$eares_roles = array( 'Πρόεδρος', 'Αντιπρόεδρος', 'Γενικός Γραμματέας', 'Ταμίας', 'Μέλος', 'Μέλος', 'Μέλος' );
?>
<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Διοικητικό Συμβούλιο</h2>
<!-- /wp:heading -->
<!-- wp:group {"align":"wide","layout":{"type":"grid","minimumColumnWidth":"14rem"}} -->
<div class="wp-block-group alignwide">
	<?php foreach ( $eares_roles as $eares_role ) : ?>
	<!-- wp:group {"className":"is-style-card","layout":{"type":"default"}} -->
	<div class="wp-block-group is-style-card">
		<!-- wp:paragraph {"className":"eares-eyebrow"} -->
		<p class="eares-eyebrow"><?php echo esc_html( $eares_role ); ?></p>
		<!-- /wp:paragraph -->
		<!-- wp:heading {"level":3} -->
		<h3 class="wp-block-heading">Ονοματεπώνυμο</h3>
		<!-- /wp:heading -->
	</div>
	<!-- /wp:group -->
	<?php endforeach; ?>
</div>
<!-- /wp:group -->
