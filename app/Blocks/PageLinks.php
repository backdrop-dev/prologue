<?php
/**
 * Page links for split posts.
 *
 * @package   Prologue
 * @author    Benjamin Lu
 * @copyright 2026 Benjamin Lu
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 * @link      https://github.com/backdrop-dev/prologue
 */

namespace Prologue\Blocks;

use Backdrop\Contracts\Bootable;
use WP_Block;

/**
 * Adds "Pages: 1 2 3" links after the content of a post that's split into
 * pages with the Page Break block.
 *
 * The Post Content block shows only the current page and, unlike classic
 * themes, there's no core block for the links to the other pages.
 */
class PageLinks implements Bootable {

	/**
	 * Adds the hooks.
	 */
	public function boot(): void {

		add_filter( 'render_block_core/post-content', [ $this, 'addLinks' ], 10, 3 );
	}

	/**
	 * Appends the page links to the Post Content block of the post being
	 * viewed.
	 *
	 * @param  string   $content  The rendered content.
	 * @param  array    $block    The block's name and attributes.
	 * @param  WP_Block $instance The block instance.
	 * @return string
	 */
	public function addLinks( $content, $block, $instance = null ): string {

		$content = (string) $content;
		$post_id = $instance instanceof WP_Block && isset( $instance->context['postId'] )
			? (int) $instance->context['postId']
			: 0;

		// Only the main post on its own page: `$multipage` describes it.
		if ( ! $post_id || ! is_singular() || get_queried_object_id() !== $post_id || empty( $GLOBALS['multipage'] ) ) {
			return $content;
		}

		$links = wp_link_pages( [
			'before' => '<nav class="prologue-page-links" aria-label="' . esc_attr__( 'Pages', 'prologue' ) . '"><span class="prologue-page-links__label">' . esc_html__( 'Pages:', 'prologue' ) . '</span>',
			'after'  => '</nav>',
			'echo'   => false,
		] );

		return $content . $links;
	}
}
