<?php
/**
 * Title: Footer
 * Slug: prologue/footer
 * Categories: footer
 * Block Types: core/template-part/footer
 * Inserter: yes
 *
 * @package Prologue
 */

?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"backgroundColor":"muted","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-muted-background-color has-background" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)"><!-- wp:group {"align":"wide","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
<div class="wp-block-group alignwide"><!-- wp:site-title {"level":0} /-->

<!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size"><?php
	printf(
		/* translators: %s: WordPress link. */
		esc_html__( 'Built with %s and Prologue.', 'prologue' ),
		'<a href="' . esc_url( __( 'https://wordpress.org', 'prologue' ) ) . '">WordPress</a>'
	);
?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
