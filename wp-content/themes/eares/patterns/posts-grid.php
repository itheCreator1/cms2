<?php
/**
 * Title: Λίστα άρθρων (πλέγμα)
 * Slug: eares/posts-grid
 * Categories: eares
 * Inserter: no
 *
 * The main query as a card grid, for the news page, archives and search.
 *
 * @package eares
 */

?>
<!-- wp:query {"queryId":21,"query":{"inherit":true},"align":"wide","className":"eares-grid"} -->
<div class="wp-block-query alignwide eares-grid">
	<!-- wp:post-template {"layout":{"type":"grid","minimumColumnWidth":"18rem"}} -->
		<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/9","sizeSlug":"medium_large"} /-->
		<!-- wp:post-terms {"term":"category"} /-->
		<!-- wp:post-title {"level":2,"isLink":true} /-->
		<!-- wp:post-excerpt {"excerptLength":28} /-->
		<!-- wp:post-date /-->
	<!-- /wp:post-template -->
	<!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"space-between"}} -->
		<!-- wp:query-pagination-previous {"label":"Νεότερα"} /-->
		<!-- wp:query-pagination-numbers /-->
		<!-- wp:query-pagination-next {"label":"Παλαιότερα"} /-->
	<!-- /wp:query-pagination -->
	<!-- wp:query-no-results -->
		<!-- wp:paragraph -->
		<p>Δεν βρέθηκαν άρθρα.</p>
		<!-- /wp:paragraph -->
	<!-- /wp:query-no-results -->
</div>
<!-- /wp:query -->
