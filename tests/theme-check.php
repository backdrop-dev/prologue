<?php
/**
 * Runs the Theme Check plugin's checks against an installed theme.
 *
 * Usage: php tests/theme-check.php /path/to/site /path/to/theme-check theme-slug
 *
 * Prints every message, grouped by type. Exits 1 if any check fails
 * (REQUIRED) or there's a WARNING, so the theme is ready for WordPress.org
 * review. RECOMMENDED and INFO messages are printed but don't fail.
 *
 * @package Prologue
 */

// WordPress uses $plugin and $theme as globals while loading, so keep
// the arguments under names it doesn't touch.
[ , $prologue_site, $prologue_checker, $prologue_slug ] = $argv + [ '', '', '', '' ];

if ( ! $prologue_site || ! $prologue_checker || ! $prologue_slug ) {
	fwrite( STDERR, "Usage: php tests/theme-check.php /path/to/site /path/to/theme-check theme-slug\n" );
	exit( 1 );
}

$_SERVER['HTTP_HOST'] = 'localhost';

require "{$prologue_site}/wp-load.php";
require_once "{$prologue_checker}/checkbase.php";

$slug  = $prologue_slug;
$theme = wp_get_theme( $slug );

if ( ! $theme->exists() ) {
	fwrite( STDERR, "Theme {$slug} not found.\n" );
	exit( 1 );
}

$passed = run_themechecks_against_theme( $theme, $slug );

$messages = [];

foreach ( $GLOBALS['themechecks'] as $check ) {
	if ( ! $check instanceof themecheck ) {
		continue;
	}

	foreach ( (array) $check->getError() as $message ) {
		$type  = 'OTHER';
		$text  = $message;

		if ( preg_match( '/<span[^>]*>(.*?)<\/span>(.*)/s', $message, $matches ) ) {
			$type = trim( rtrim( wp_strip_all_tags( $matches[1] ), ':' ) );
			$text = $matches[2];
		}

		$text = trim( ltrim( html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES, 'UTF-8' ), ': ' ) );
		$text = str_replace( ABSPATH, '', $text );

		$messages[ $type ][] = preg_replace( '/\s+/', ' ', $text );
	}
}

ksort( $messages );

foreach ( $messages as $type => $list ) {
	foreach ( array_unique( $list ) as $text ) {
		echo "{$type}: {$text}\n";
	}
}

$failed = ! $passed || ! empty( $messages['REQUIRED'] ) || ! empty( $messages['WARNING'] );

printf(
	"Theme Check %s for %s: %d required, %d warnings, %d recommended, %d info.\n",
	$failed ? 'failed' : 'passed',
	$slug,
	count( $messages['REQUIRED'] ?? [] ),
	count( $messages['WARNING'] ?? [] ),
	count( $messages['RECOMMENDED'] ?? [] ),
	count( $messages['INFO'] ?? [] )
);

exit( $failed ? 1 : 0 );
