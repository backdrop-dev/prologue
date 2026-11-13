<?php
/**
 * Block styles.
 *
 * @package   Prologue
 * @author    Benjamin Lu
 * @copyright 2026 Benjamin Lu
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 * @link      https://github.com/backdrop-dev/prologue
 */

namespace Prologue\Blocks;

use Backdrop\Contracts\Bootable;

/**
 * Registers the theme's block styles, the extra options in a block's Styles
 * panel.
 */
class BlockStyles implements Bootable {

	/**
	 * Adds the hooks.
	 */
	public function boot(): void {

		add_action( 'init', [ $this, 'register' ] );
	}

	/**
	 * Registers each block style.
	 */
	public function register(): void {

		foreach ( $this->styles() as $block => $styles ) {
			foreach ( $styles as $style ) {
				register_block_style( $block, $style );
			}
		}
	}

	/**
	 * Returns the block styles, keyed by block name.
	 *
	 * `style_data` takes the same format as a block's styles in `theme.json`,
	 * so the styles follow the active style variation's colors.
	 *
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	public function styles(): array {

		$styles = [
			'core/group'  => [
				[
					'name'       => 'card',
					'label'      => __( 'Card', 'prologue' ),
					'style_data' => [
						'color'   => [
							'background' => 'var:preset|color|muted',
							'text'       => 'var:preset|color|contrast',
						],
						'border'  => [
							'radius' => '0.5rem',
						],
						'spacing' => [
							'padding' => [
								'top'    => 'var:preset|spacing|50',
								'right'  => 'var:preset|spacing|50',
								'bottom' => 'var:preset|spacing|50',
								'left'   => 'var:preset|spacing|50',
							],
						],
					],
				],
			],
			'core/button' => [
				[
					'name'       => 'pill',
					'label'      => __( 'Pill', 'prologue' ),
					'style_data' => [
						'border' => [
							'radius' => '999px',
						],
					],
				],
			],
		];

		return apply_filters( 'prologue/block-styles', $styles );
	}
}
