<?php
/**
 * Stylesheets.
 *
 * @package   Prologue
 * @author    Benjamin Lu
 * @copyright 2026 Benjamin Lu
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 * @link      https://github.com/backdrop-dev/prologue
 */

namespace Prologue\Assets;

use Backdrop\Contracts\Bootable;

/**
 * Loads the theme's global stylesheet and its stylesheets for single blocks.
 */
class Assets implements Bootable {

	/**
	 * Adds the hooks.
	 */
	public function boot(): void {

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueueStyles' ] );
		add_action( 'after_setup_theme', [ $this, 'addEditorStyles' ] );
		add_action( 'init', [ $this, 'enqueueBlockStyles' ] );
	}

	/**
	 * Loads `public/css/style.css` on the front end.
	 */
	public function enqueueStyles(): void {

		wp_enqueue_style(
			'prologue-style',
			get_parent_theme_file_uri( 'public/css/style.css' ),
			[],
			wp_get_theme( get_template() )->get( 'Version' )
		);
	}

	/**
	 * Loads the same stylesheet in the editor.
	 */
	public function addEditorStyles(): void {

		add_editor_style( 'public/css/style.css' );
	}

	/**
	 * Loads each stylesheet in `public/css/blocks/` only on pages that use
	 * its block, in the front end and the editor.
	 *
	 * Name a file after its block, with the slash replaced by a dash: the
	 * styles for `core/quote` go in `core-quote.css`.
	 */
	public function enqueueBlockStyles(): void {

		$files = glob( get_parent_theme_file_path( 'public/css/blocks/*.css' ) );

		foreach ( $files ? $files : [] as $file ) {

			$name  = basename( $file, '.css' );
			$parts = explode( '-', $name, 2 );

			if ( count( $parts ) !== 2 ) {
				continue;
			}

			wp_enqueue_block_style( "{$parts[0]}/{$parts[1]}", [
				'handle' => "prologue-{$name}",
				'src'    => get_parent_theme_file_uri( "public/css/blocks/{$name}.css" ),
				'path'   => $file,
				'ver'    => wp_get_theme( get_template() )->get( 'Version' ),
			] );
		}
	}
}
