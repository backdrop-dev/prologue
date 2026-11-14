<?php
/**
 * Comments by the post's author.
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
 * Adds an "Author" label after the name on comments written by the post's
 * author. WordPress adds the `bypostauthor` class to those comments, and
 * `public/css/blocks/core-comments.css` highlights them.
 */
class AuthorComments implements Bootable {

	/**
	 * Adds the hooks.
	 */
	public function boot(): void {

		add_filter( 'render_block_core/comment-author-name', [ $this, 'addLabel' ], 10, 3 );
	}

	/**
	 * Adds the label after the comment author's name.
	 *
	 * @param  string   $content  The rendered name.
	 * @param  array    $block    The block's name and attributes.
	 * @param  WP_Block $instance The block instance.
	 * @return string
	 */
	public function addLabel( $content, $block, $instance = null ): string {

		$content    = (string) $content;
		$comment_id = $instance instanceof WP_Block && isset( $instance->context['commentId'] )
			? (int) $instance->context['commentId']
			: 0;

		if ( ! $comment_id || ! $this->isByPostAuthor( $comment_id ) ) {
			return $content;
		}

		$label = apply_filters( 'prologue/comment-author-label', __( 'Author', 'prologue' ), $comment_id );

		if ( '' === (string) $label ) {
			return $content;
		}

		return $content . sprintf( '<span class="prologue-author-label">%s</span>', esc_html( $label ) );
	}

	/**
	 * Whether a comment was written by the author of its post, using the
	 * same test as WordPress's `bypostauthor` class.
	 *
	 * @param  int $comment_id The comment ID.
	 * @return bool
	 */
	protected function isByPostAuthor( int $comment_id ): bool {

		$comment = get_comment( $comment_id );
		$post    = $comment ? get_post( (int) $comment->comment_post_ID ) : null;

		return $comment && $post && $comment->user_id && (int) $comment->user_id === (int) $post->post_author;
	}
}
