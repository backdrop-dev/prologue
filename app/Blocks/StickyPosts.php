<?php
/**
 * Sticky posts.
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
 * Makes sticky posts stand out in the posts list on the blog's front page.
 *
 * WordPress already lists sticky posts first and adds the `sticky` class to
 * them, but only on the first page of the blog. This adds a "Featured" label
 * above the title on those same posts, and `public/css/blocks/core-post-template.css`
 * styles them.
 */
class StickyPosts implements Bootable {

	/**
	 * Adds the hooks.
	 */
	public function boot(): void {

		add_filter( 'render_block_core/post-title', [ $this, 'addLabel' ], 10, 3 );
	}

	/**
	 * Adds the label before the title of a sticky post in a posts list.
	 *
	 * @param  string   $content  The rendered title.
	 * @param  array    $block    The block's name and attributes.
	 * @param  WP_Block $instance The block instance.
	 * @return string
	 */
	public function addLabel( $content, $block, $instance = null ): string {

		$content = (string) $content;
		$post_id = $instance instanceof WP_Block && isset( $instance->context['postId'] )
			? (int) $instance->context['postId']
			: 0;

		if ( ! $post_id || ! $this->isHighlighted( $post_id ) ) {
			return $content;
		}

		$label = apply_filters( 'prologue/sticky-label', __( 'Featured', 'prologue' ), $post_id );

		if ( '' === (string) $label ) {
			return $content;
		}

		return sprintf(
			'<p class="prologue-sticky-label">%s</p>%s',
			esc_html( $label ),
			$content
		);
	}

	/**
	 * Whether a post is highlighted as sticky. Matches the pages where
	 * WordPress adds the `sticky` class: the first page of the blog.
	 *
	 * @param  int $post_id The post ID.
	 * @return bool
	 */
	protected function isHighlighted( int $post_id ): bool {

		return is_home() && ! is_paged() && is_sticky( $post_id );
	}
}
