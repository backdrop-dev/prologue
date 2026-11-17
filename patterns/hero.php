<?php
/**
 * Title: Hero
 * Slug: prologue/hero
 * Categories: prologue, banner
 * Keywords: hero, intro, banner
 * Inserter: yes
 *
 * Buttons link to https://example.com as a placeholder: replace
 * them with your own URLs.
 *
 * The heading is level 2 on purpose: the page's own title is the
 * H1, so this pattern never creates a duplicate H1.
 *
 * @package Prologue
 */

?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--70)"><!-- wp:heading {"textAlign":"center","level":2} -->
<h2 class="wp-block-heading has-text-align-center"><?php esc_html_e( 'A fresh start for your next site', 'prologue' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","fontSize":"large"} -->
<p class="has-text-align-center has-large-font-size"><?php esc_html_e( 'Introduce what you do in a sentence or two, then point visitors to where they should go next.', 'prologue' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"url":"https://example.com"} -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="https://example.com"><?php esc_html_e( 'Get started', 'prologue' ); ?></a></div>
<!-- /wp:button -->

<!-- wp:button {"url":"https://example.com","className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="https://example.com"><?php esc_html_e( 'Learn more', 'prologue' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->
