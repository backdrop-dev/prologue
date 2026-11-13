<?php
/**
 * Pattern categories.
 *
 * @package   Prologue
 * @author    Benjamin Lu
 * @copyright 2026 Benjamin Lu
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 * @link      https://github.com/backdrop-dev/prologue
 */

namespace Prologue\Patterns;

use Backdrop\Contracts\Bootable;

/**
 * Registers the pattern categories used by the files in `patterns/`.
 *
 * WordPress registers the patterns themselves from that folder. A pattern
 * joins a category with a `Categories:` line in its header.
 */
class Categories implements Bootable {

	/**
	 * Adds the hooks.
	 */
	public function boot(): void {

		add_action( 'init', [ $this, 'register' ] );
	}

	/**
	 * Registers each category.
	 */
	public function register(): void {

		foreach ( $this->categories() as $name => $label ) {
			register_block_pattern_category( $name, [ 'label' => $label ] );
		}
	}

	/**
	 * Returns the categories as name => label.
	 *
	 * @return array<string, string>
	 */
	public function categories(): array {

		return apply_filters( 'prologue/pattern-categories', [
			'prologue' => __( 'Prologue', 'prologue' ),
		] );
	}
}
