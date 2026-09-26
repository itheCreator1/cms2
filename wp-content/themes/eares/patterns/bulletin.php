<?php
/**
 * Title: Νέα, Ριζαρείτης και εκδηλώσεις (αρχική)
 * Slug: eares/bulletin
 * Categories: eares
 * Inserter: no
 *
 * The dense part of the front page: the latest news on the left, the latest
 * Ριζαρείτης front page and upcoming events on the right.
 *
 * @package eares
 */

$eares_news      = wp_json_encode( array( 'category' => eares_theme_news_category_ids() ) );
$eares_issues    = wp_json_encode( array( 'category' => eares_theme_category_ids( array( 'rizareitis' ) ) ) );
$eares_events    = wp_json_encode( array( 'category' => eares_theme_category_ids( array( 'ekdiloseis' ) ) ) );
$eares_news_page = get_page_by_path( 'nea' );
$eares_news_url  = $eares_news_page ? get_permalink( $eares_news_page ) : home_url( '/nea/' );
$eares_issue_cat = get_category_by_slug( 'rizareitis' );
$eares_event_cat = get_category_by_slug( 'ekdiloseis' );
?>
<!-- wp:group {"align":"wide","className":"eares-bulletin","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide eares-bulletin">
<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|60"}}}} -->
<div class="wp-block-columns">
	<!-- wp:column {"width":"66.66%"} -->
	<div class="wp-block-column" style="flex-basis:66.66%">
		<!-- wp:group {"className":"eares-section-head","layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap"}} -->
		<div class="wp-block-group eares-section-head">
			<!-- wp:heading {"level":2} -->
			<h2 class="wp-block-heading">Νέα &amp; ανακοινώσεις</h2>
			<!-- /wp:heading -->
			<!-- wp:paragraph -->
			<p><a href="<?php echo esc_url( $eares_news_url ); ?>">Όλα τα νέα →</a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:query {"queryId":12,"query":{"perPage":1,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","sticky":"exclude","inherit":false,"taxQuery":<?php echo $eares_news; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON of integers. ?>},"className":"eares-lead"} -->
		<div class="wp-block-query eares-lead">
			<!-- wp:post-template -->
				<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/9","sizeSlug":"large"} /-->
				<!-- wp:post-terms {"term":"category"} /-->
				<!-- wp:post-title {"level":3,"isLink":true} /-->
				<!-- wp:post-excerpt {"excerptLength":40} /-->
				<!-- wp:post-date /-->
			<!-- /wp:post-template -->
			<!-- wp:query-no-results -->
				<!-- wp:paragraph -->
				<p>Δεν υπάρχουν ακόμη νέα.</p>
				<!-- /wp:paragraph -->
			<!-- /wp:query-no-results -->
		</div>
		<!-- /wp:query -->

		<!-- wp:query {"queryId":13,"query":{"perPage":6,"pages":0,"offset":1,"postType":"post","order":"desc","orderBy":"date","sticky":"exclude","inherit":false,"taxQuery":<?php echo $eares_news; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON of integers. ?>},"className":"eares-newslist"} -->
		<div class="wp-block-query eares-newslist">
			<!-- wp:post-template -->
				<!-- wp:post-date /-->
				<!-- wp:post-title {"level":3,"isLink":true} /-->
				<!-- wp:post-terms {"term":"category"} /-->
			<!-- /wp:post-template -->
		</div>
		<!-- /wp:query -->
	</div>
	<!-- /wp:column -->

	<!-- wp:column {"width":"33.33%","className":"eares-aside"} -->
	<div class="wp-block-column eares-aside" style="flex-basis:33.33%">
		<!-- wp:group {"className":"is-style-card eares-issue","layout":{"type":"default"}} -->
		<div class="wp-block-group is-style-card eares-issue">
			<!-- wp:paragraph {"className":"eares-eyebrow"} -->
			<p class="eares-eyebrow">Πρωτοσέλιδο</p>
			<!-- /wp:paragraph -->
			<!-- wp:heading {"level":2} -->
			<h2 class="wp-block-heading">Ο Ριζαρείτης</h2>
			<!-- /wp:heading -->
			<!-- wp:query {"queryId":14,"query":{"perPage":1,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","sticky":"","inherit":false,"taxQuery":<?php echo $eares_issues; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON of integers. ?>}} -->
			<div class="wp-block-query">
				<!-- wp:post-template -->
					<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"3/4","sizeSlug":"large"} /-->
					<!-- wp:post-title {"level":3,"isLink":true} /-->
					<!-- wp:post-date /-->
				<!-- /wp:post-template -->
				<!-- wp:query-no-results -->
					<!-- wp:paragraph -->
					<p>Το πρώτο φύλλο έρχεται σύντομα.</p>
					<!-- /wp:paragraph -->
				<!-- /wp:query-no-results -->
			</div>
			<!-- /wp:query -->
			<?php if ( $eares_issue_cat ) : ?>
			<!-- wp:paragraph -->
			<p><a href="<?php echo esc_url( get_category_link( $eares_issue_cat ) ); ?>">Όλα τα φύλλα →</a></p>
			<!-- /wp:paragraph -->
			<?php endif; ?>
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"eares-events","layout":{"type":"default"}} -->
		<div class="wp-block-group eares-events">
			<!-- wp:group {"className":"eares-section-head","layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap"}} -->
			<div class="wp-block-group eares-section-head">
				<!-- wp:heading {"level":2} -->
				<h2 class="wp-block-heading">Εκδηλώσεις</h2>
				<!-- /wp:heading -->
				<?php if ( $eares_event_cat ) : ?>
				<!-- wp:paragraph -->
				<p><a href="<?php echo esc_url( get_category_link( $eares_event_cat ) ); ?>">Όλες →</a></p>
				<!-- /wp:paragraph -->
				<?php endif; ?>
			</div>
			<!-- /wp:group -->
			<!-- wp:query {"queryId":15,"query":{"perPage":4,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","sticky":"","inherit":false,"taxQuery":<?php echo $eares_events; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON of integers. ?>}} -->
			<div class="wp-block-query">
				<!-- wp:post-template -->
					<!-- wp:post-title {"level":3,"isLink":true} /-->
					<!-- wp:post-date /-->
				<!-- /wp:post-template -->
				<!-- wp:query-no-results -->
					<!-- wp:paragraph -->
					<p>Καμία προγραμματισμένη εκδήλωση.</p>
					<!-- /wp:paragraph -->
				<!-- /wp:query-no-results -->
			</div>
			<!-- /wp:query -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:column -->
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->
