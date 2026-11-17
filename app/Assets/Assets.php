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
		add_action( 'after_setup_theme', [ $this, 'loadTextDomain' ] );
		add_action( 'init', [ $this, 'enqueueBlockStyles' ] );
	}

	/**
	 * Returns the theme version, resolved once per request.
	 */
	private function version(): string {
		static $version = null;

		if ( null === $version ) {
			$version = (string) wp_get_theme( get_template() )->get( 'Version' );
		}

		return $version;
	}

	/**
	 * Loads the theme text domain. WordPress loads it automatically for
	 * most themes, but a renamed theme (or an older install) should not
	 * silently lose its translations.
	 */
	public function loadTextDomain(): void {
		load_theme_textdomain( 'prologue', get_parent_theme_file_path( 'languages' ) );
	}

	/**
	 * Loads `public/css/style.css` on the front end.
	 */
	public function enqueueStyles(): void {

		wp_enqueue_style(
			'prologue-style',
			get_parent_theme_file_uri( 'public/css/style.css' ),
			[],
			$this->version()
		);
	}

	/**
	 * Loads the editor stylesheet in the editor.
	 *
	 * This is `public/css/editor.css`, not `public/css/style.css`: the
	 * front-end layout rules in `style.css` (the flex column that pins
	 * the footer to the bottom, and the `main` padding) would leak into
	 * the editor canvas if that file were loaded there.
	 */
	public function addEditorStyles(): void {

		add_editor_style( 'public/css/editor.css' );
	}

	/**
	 * Loads each stylesheet in `public/css/blocks/` only on pages that use
	 * its block, in the front end and the editor.
	 *
	 * Name a file after its block, with the slash replaced by a dash: the
	 * styles for `core/quote` go in `core-quote.css`.
	 */
	public function enqueueBlockStyles(): void {

		$version = $this->version();
		$files   = glob( get_parent_theme_file_path( 'public/css/blocks/*.css' ) );

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
				'ver'    => $version,
			] );
		}
	}
}
