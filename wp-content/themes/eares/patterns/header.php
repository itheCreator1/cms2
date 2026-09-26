<?php
/**
 * Title: Κεφαλίδα
 * Slug: eares/header
 * Categories: eares
 * Inserter: no
 *
 * @package eares
 */

?>
<!-- wp:group {"className":"eares-topbar","layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group eares-topbar">
	<!-- wp:group {"layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap"}} -->
	<div class="wp-block-group">
		<!-- wp:paragraph -->
		<p>Ριζάρειος Εκκλησιαστική Σχολή · από το 1844</p>
		<!-- /wp:paragraph -->
		<!-- wp:loginout /-->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"eares-masthead","layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group eares-masthead">
	<!-- wp:group {"className":"eares-masthead__inner","layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap"}} -->
	<div class="wp-block-group eares-masthead__inner">
		<!-- wp:group {"className":"eares-brand","layout":{"type":"flex","flexWrap":"nowrap"}} -->
		<div class="wp-block-group eares-brand">
			<!-- wp:image {"width":"52px","height":"52px","className":"eares-brand__mark"} -->
			<figure class="wp-block-image is-resized eares-brand__mark"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/emblem.svg' ) ); ?>" alt="" style="width:52px;height:52px"/></figure>
			<!-- /wp:image -->
			<!-- wp:group {"className":"eares-brand__text","layout":{"type":"flex","orientation":"vertical"}} -->
			<div class="wp-block-group eares-brand__text">
				<!-- wp:site-title {"level":0,"className":"eares-brand__name"} /-->
				<!-- wp:site-tagline {"className":"eares-brand__tagline"} /-->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:group -->
		<!-- wp:group {"className":"eares-nav-wrap","layout":{"type":"flex","flexWrap":"nowrap"}} -->
		<div class="wp-block-group eares-nav-wrap">
			<!-- wp:navigation {"overlayMenu":"mobile","overlayBackgroundColor":"parchment","overlayTextColor":"crimson","className":"eares-nav","layout":{"type":"flex","justifyContent":"right","flexWrap":"wrap"}} -->
			<?php foreach ( eares_theme_menu_items() as $eares_link ) : ?>
			<!-- wp:navigation-link <?php echo wp_json_encode( $eares_link, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?> /-->
			<?php endforeach; ?>
			<!-- /wp:navigation -->
			<!-- wp:search {"label":"Αναζήτηση","showLabel":false,"placeholder":"Αναζήτηση…","buttonText":"Αναζήτηση","buttonPosition":"button-only","buttonUseIcon":true,"isSearchFieldHidden":true} /-->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
