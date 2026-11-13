<?php
/**
 * Assets service provider.
 *
 * @package   Prologue
 * @author    Benjamin Lu
 * @copyright 2026 Benjamin Lu
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 * @link      https://github.com/backdrop-dev/prologue
 */

namespace Prologue\Assets;

use Backdrop\Core\ServiceProvider;

/**
 * Binds and boots the assets component.
 */
class Provider extends ServiceProvider {

	/**
	 * Registers the component as a single shared instance.
	 */
	public function register(): void {

		$this->app->singleton( Assets::class );
	}

	/**
	 * Boots the component, which adds its WordPress hooks.
	 */
	public function boot(): void {

		$this->app->resolve( Assets::class )->boot();
	}
}
