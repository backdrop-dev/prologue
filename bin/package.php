<?php
/**
 * Builds the theme's release zip, ready to upload to a site or submit to the
 * WordPress.org theme directory.
 *
 * Usage: php bin/package.php [output-directory]
 *
 * Copies the theme into a temporary folder named after its slug (the Text
 * Domain in style.css), installs the Composer dependencies there without
 * development packages, and zips it as `{slug}-{version}.zip` in `dist/`
 * (or the given directory).
 *
 * Left out of the zip: development files (tests, bin, GitHub workflows,
 * README.md, Composer files, node_modules) and anything hidden. The build
 * stops if the result would contain a file WordPress.org doesn't allow.
 *
 * @package Prologue
 */

$root   = dirname( __DIR__ );
$output = $argv[1] ?? "{$root}/dist";

/**
 * Prints a message to standard error and exits.
 *
 * @param string $message Message.
 */
function package_fail( string $message ): void {
	fwrite( STDERR, $message . "\n" );
	exit( 1 );
}

/**
 * Reads a header from style.css, such as "Text Domain".
 *
 * @param string $css    The contents of style.css.
 * @param string $header The header name.
 * @return string
 */
function package_header( string $css, string $header ): string {
	return preg_match( '/^[ \t\/*#@]*' . preg_quote( $header, '/' ) . ':(.*)$/mi', $css, $match )
		? trim( $match[1] )
		: '';
}

/**
 * Deletes a folder and everything in it.
 *
 * @param string $path Folder path.
 */
function package_remove( string $path ): void {
	if ( ! is_dir( $path ) ) {
		return;
	}

	$items = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $path, FilesystemIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::CHILD_FIRST
	);

	foreach ( $items as $item ) {
		$item->isDir() && ! $item->isLink() ? rmdir( $item->getPathname() ) : unlink( $item->getPathname() );
	}

	rmdir( $path );
}

$css     = (string) file_get_contents( "{$root}/style.css" );
$slug    = package_header( $css, 'Text Domain' );
$version = package_header( $css, 'Version' );

if ( ! preg_match( '/^[a-z0-9-]+$/', $slug ) || '' === $version ) {
	package_fail( 'style.css needs a lowercase Text Domain (the theme slug) and a Version.' );
}

// Development files and folders, relative to the theme root.
$exclude = [
	'bin',
	'dist',
	'node_modules',
	'tests',
	'vendor',
	'README.md',
	'composer.json',
	'composer.lock',
];

$build = sys_get_temp_dir() . '/theme-package-' . getmypid();
$stage = "{$build}/{$slug}";

package_remove( $build );
mkdir( $stage, 0777, true );

// Copy the theme, skipping development and hidden files.
$files = new RecursiveIteratorIterator(
	new RecursiveCallbackFilterIterator(
		new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ),
		function ( SplFileInfo $file ) use ( $root, $exclude ) {
			$relative = ltrim( substr( $file->getPathname(), strlen( $root ) ), '/' );

			return '.' !== $file->getFilename()[0] && ! in_array( $relative, $exclude, true );
		}
	),
	RecursiveIteratorIterator::SELF_FIRST
);

foreach ( $files as $file ) {
	$target = $stage . substr( $file->getPathname(), strlen( $root ) );

	if ( $file->isDir() ) {
		@mkdir( $target, 0777, true );
	} else {
		copy( $file->getPathname(), $target );
	}
}

// Install the dependencies the theme needs at runtime.
copy( "{$root}/composer.json", "{$stage}/composer.json" );
copy( "{$root}/composer.lock", "{$stage}/composer.lock" );

$command = sprintf(
	'composer install --no-dev --optimize-autoloader --no-interaction --no-progress --working-dir=%s 2>&1',
	escapeshellarg( $stage )
);

exec( $command, $composer_output, $status );

if ( 0 !== $status ) {
	package_fail( "composer install failed:\n" . implode( "\n", $composer_output ) );
}

unlink( "{$stage}/composer.json" );
unlink( "{$stage}/composer.lock" );

// Hidden files aren't allowed. Composer can leave some in packages (for
// example a `.git` folder when it installs from source), so remove them.
$hidden = [];

$staged = new RecursiveIteratorIterator(
	new RecursiveCallbackFilterIterator(
		new RecursiveDirectoryIterator( $stage, FilesystemIterator::SKIP_DOTS ),
		function ( SplFileInfo $file ) use ( &$hidden ) {
			if ( '.' === $file->getFilename()[0] ) {
				$hidden[] = $file->getPathname();
				return false;
			}
			return true;
		}
	),
	RecursiveIteratorIterator::SELF_FIRST
);

iterator_to_array( $staged );

foreach ( $hidden as $path ) {
	is_dir( $path ) && ! is_link( $path ) ? package_remove( $path ) : unlink( $path );
}

// Check the result against WordPress.org's file rules.
$disallowed = '/\.(sh|sql|dat|wie|lubith|kpf|zip|phar|log)$|(^|\/)(thumbs\.db|desktop\.ini|php\.ini|web\.config|error_log|favicon\.ico|project\.xml|project\.properties|dwsync\.xml|__MACOSX)$/i';
$problems   = [];
$count      = 0;

$staged = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator( $stage, FilesystemIterator::SKIP_DOTS ),
	RecursiveIteratorIterator::SELF_FIRST
);

foreach ( $staged as $file ) {
	$relative = ltrim( substr( $file->getPathname(), strlen( $stage ) ), '/' );

	if ( preg_match( $disallowed, $relative ) || ( preg_match( '/\.xml$/i', $relative ) && ! preg_match( '/(^|\/)(wpml-config|loco|phpcs)\.xml$/i', $relative ) ) ) {
		$problems[] = $relative;
	}

	if ( $file->isFile() ) {
		$count++;
	}
}

if ( $problems ) {
	package_fail( "These files aren't allowed in a WordPress.org theme:\n  " . implode( "\n  ", $problems ) );
}

foreach ( [ 'style.css', 'readme.txt', 'theme.json', 'templates/index.html', 'vendor/autoload.php' ] as $required ) {
	if ( ! is_file( "{$stage}/{$required}" ) ) {
		package_fail( "The theme is missing {$required}." );
	}
}

if ( ! glob( "{$stage}/screenshot.{png,jpg}", GLOB_BRACE ) ) {
	package_fail( 'The theme is missing screenshot.png.' );
}

// Zip it, with the slug as the top-level folder.
if ( ! is_dir( $output ) ) {
	mkdir( $output, 0777, true );
}

$zip_file = rtrim( $output, '/' ) . "/{$slug}-{$version}.zip";

if ( is_file( $zip_file ) ) {
	unlink( $zip_file );
}

exec( sprintf( 'cd %s && zip -qrX %s %s 2>&1', escapeshellarg( $build ), escapeshellarg( $zip_file ), escapeshellarg( $slug ) ), $zip_output, $status );

package_remove( $build );

if ( 0 !== $status ) {
	package_fail( "zip failed:\n" . implode( "\n", $zip_output ) );
}

printf( "Built %s (%d files, %s KB).\n", $zip_file, $count, number_format( filesize( $zip_file ) / 1024 ) );
