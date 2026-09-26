<?php
/**
 * Title: Καρφιτσωμένη ανακοίνωση
 * Slug: eares/pinned
 * Categories: eares
 * Inserter: no
 *
 * Sticky posts ("Καρφίτσωμα στην αρχική") as notice cards under the hero.
 * Renders nothing when no post is sticky.
 *
 * @package eares
 */

?>
<!-- wp:query {"queryId":11,"query":{"perPage":2,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","sticky":"only","inherit":false},"align":"wide","className":"eares-pinned"} -->
<div class="wp-block-query alignwide eares-pinned">
	<!-- wp:post-template -->
		<!-- wp:group {"className":"is-style-notice","layout":{"type":"default"}} -->
		<div class="wp-block-group is-style-notice">
			<!-- wp:paragraph {"className":"eares-eyebrow"} -->
			<p class="eares-eyebrow">Ανακοίνωση</p>
			<!-- /wp:paragraph -->
			<!-- wp:post-title {"level":2,"isLink":true} /-->
			<!-- wp:post-excerpt {"moreText":"Διαβάστε περισσότερα →","excerptLength":40} /-->
		</div>
		<!-- /wp:group -->
	<!-- /wp:post-template -->
</div>
<!-- /wp:query -->
