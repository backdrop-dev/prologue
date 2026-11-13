<?php
/**
 * Theme bootstrap.
 *
 * Loads Composer's autoloader and boots the Backdrop application with the
 * theme's service providers.
 *
 * @package   Prologue
 * @author    Benjamin Lu
 * @copyright 2026 Benjamin Lu
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 * @link      https://github.com/backdrop-dev/prologue
 */

namespace Prologue;

# ------------------------------------------------------------------------------
# Autoload.
# ------------------------------------------------------------------------------
#
# Loads the classes in `app/` and the Backdrop framework. If `composer install`
# hasn't been run yet, show a notice instead of failing with a fatal error.

if ( ! file_exists( get_parent_theme_file_path( 'vendor/autoload.php' ) ) ) {

	add_action( 'admin_notices', function () {
		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html__( 'Prologue needs its dependencies. Run `composer install` in the theme folder.', 'prologue' )
		);
	} );

	return;
}

require_once get_parent_theme_file_path( 'vendor/autoload.php' );

# ------------------------------------------------------------------------------
# Create the application.
# ------------------------------------------------------------------------------
#
# Reuse an application that a plugin has already booted, or create a new one.

$prologue = \Backdrop\booted() ? \Backdrop\app() : new \Backdrop\Core\Application();

# ------------------------------------------------------------------------------
# Register the theme's service providers.
# ------------------------------------------------------------------------------

$prologue->provider( Assets\Provider::class );
$prologue->provider( Blocks\Provider::class );
$prologue->provider( Patterns\Provider::class );

# ------------------------------------------------------------------------------
# Let child themes and plugins add their own providers.
# ------------------------------------------------------------------------------

do_action( 'prologue/bootstrap', $prologue );

# ------------------------------------------------------------------------------
# Boot the application.
# ------------------------------------------------------------------------------

$prologue->boot();
