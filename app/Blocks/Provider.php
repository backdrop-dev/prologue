<?php
/**
 * Blocks service provider.
 *
 * @package   Prologue
 * @author    Benjamin Lu
 * @copyright 2026 Benjamin Lu
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 * @link      https://github.com/backdrop-dev/prologue
 */

namespace Prologue\Blocks;

use Backdrop\Core\ServiceProvider;

/**
 * Binds and boots the block components: block styles and sticky posts.
 */
class Provider extends ServiceProvider {

	/**
	 * Registers each component as a single shared instance.
	 */
	public function register(): void {

		$this->app->singleton( BlockStyles::class );
		$this->app->singleton( StickyPosts::class );
	}

	/**
	 * Boots each component, which adds its WordPress hooks.
	 */
	public function boot(): void {

		$this->app->resolve( BlockStyles::class )->boot();
		$this->app->resolve( StickyPosts::class )->boot();
	}
}
