<?php

// This script is for the command line only: running it from a browser
// must not install dependencies, build a zip or rewrite theme files.
if ( 'cli' !== PHP_SAPI ) {
	exit;
}
/**
 * Renames Prologue to your own theme.
 *
 * Usage:
 *   php bin/rename.php "Theme Name" [options]
 *
 * Options:
 *   --slug=theme-name         Folder name, text domain and prefix. Defaults to
 *                             the name in lowercase with dashes.
 *   --namespace=ThemeName     PHP namespace. Defaults to the name in PascalCase.
 *   --package=vendor/name     Composer package name. Defaults to {slug}/{slug}.
 *   --description="…"         Description for style.css and readme.txt.
 *   --author="Your Name"      Author and copyright holder.
 *   --author-uri=https://…    Your website.
 *   --theme-uri=https://…     The theme's page. Leave it out to drop the
 *                             Theme URI header and the @link lines.
 *   --contributor=username    Your WordPress.org username, for readme.txt.
 *
 * Replaces the name, slug, prefixes, namespace and author throughout the
 * theme, including its tests, so the renamed theme still passes them. It
 * doesn't rename the theme's folder: rename that yourself to match the slug.
 * Run `composer dump-autoload` afterwards for the new namespace.
 *
 * @package Prologue
 */

$root = dirname( __DIR__ );
$args = [];
$name = '';

foreach ( array_slice( $argv, 1 ) as $arg ) {
	if ( preg_match( '/^--([a-z-]+)=(.*)$/s', $arg, $match ) ) {
		$args[ $match[1] ] = $match[2];
	} elseif ( '' === $name ) {
		$name = trim( $arg );
	}
}

if ( '' === $name ) {
	fwrite( STDERR, "Usage: php bin/rename.php \"Theme Name\" [--slug=theme-name] [--namespace=ThemeName] [--package=vendor/name] [--description=\"…\"] [--author=\"Your Name\"] [--author-uri=https://…] [--theme-uri=https://…] [--contributor=username]\n" );
	exit( 1 );
}

$words     = preg_split( '/[^A-Za-z0-9]+/', $name, -1, PREG_SPLIT_NO_EMPTY );
$slug      = $args['slug'] ?? strtolower( implode( '-', $words ) );
$namespace = $args['namespace'] ?? implode( '', array_map( 'ucfirst', $words ) );
$package   = $args['package'] ?? "{$slug}/{$slug}";
$underscore = str_replace( '-', '_', $slug );

$errors = [];

if ( ! preg_match( '/^[a-z][a-z0-9-]*[a-z0-9]$/', $slug ) || strlen( $slug ) < 4 ) {
	$errors[] = "The slug \"{$slug}\" must be at least four lowercase letters, numbers or dashes, starting with a letter.";
}

if ( ! preg_match( '/^[A-Z][A-Za-z0-9]*$/', $namespace ) ) {
	$errors[] = "The namespace \"{$namespace}\" must be a single PascalCase word.";
}

if ( ! preg_match( '/^[a-z0-9-]+\/[a-z0-9-]+$/', $package ) ) {
	$errors[] = "The package name \"{$package}\" must look like vendor/name.";
}

if ( preg_match( '/\b(wordpress|theme|twenty)/i', $name ) ) {
	$errors[] = 'WordPress.org doesn\'t allow "WordPress", "Theme" or "Twenty" in a theme name.';
}

if ( 'prologue' === $slug ) {
	$errors[] = 'Choose a name other than Prologue.';
}

if ( $errors ) {
	fwrite( STDERR, implode( "\n", $errors ) . "\n" );
	exit( 1 );
}

// Ordered replacements: the most specific first.
$replace = [];

$replace['/backdrop-dev\/prologue(?![\w-])/'] = $package;
$replace['/\bPrologue(?=\\\\|;)/']           = $namespace;
$replace['/(?<![\w-])prologue(?=_)/']        = $underscore;
$replace['/\$prologue\b/']                   = '$' . $underscore;
$replace['/(?<![\w-])prologue\b/']           = $slug;
$replace['/\bPrologue\b/']                   = $name;

// Author details.
$author_replace = [];

if ( isset( $args['author'] ) ) {
	$author_replace['Benjamin Lu'] = $args['author'];
}

if ( isset( $args['author-uri'] ) ) {
	$author_replace['https://github.com/techkodeninja'] = $args['author-uri'];
}

if ( isset( $args['contributor'] ) ) {
	$author_replace['Contributors: techkodeninja'] = 'Contributors: ' . $args['contributor'];
}

// Files to change: text files, outside dependencies, build output and this folder.
$skip_dirs  = [ '.git', 'bin', 'dist', 'node_modules', 'vendor' ];
// `bin/` is skipped by the main pass on purpose: renaming the tool
// scripts' own code (their slug checks, usage text and messages)
// would break them for a second rename. They are development tools,
// left out of the release zip, not theme code. Only their `@package`
// line is updated, in the pass below, so the renamed theme's files
// do not still claim to be Prologue.
$skip_files = [ 'LICENSE', 'composer.lock' ];
$extensions = '/\.(php|html|json|css|js|md|txt|sh|yml|yaml|xml|pot)$/';
$changed    = 0;

$files = new RecursiveIteratorIterator(
	new RecursiveCallbackFilterIterator(
		new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ),
		function ( SplFileInfo $file ) use ( $root, $skip_dirs ) {
			$relative = ltrim( substr( $file->getPathname(), strlen( $root ) ), '/' );
			return ! ( $file->isDir() && in_array( $relative, $skip_dirs, true ) );
		}
	)
);

foreach ( $files as $file ) {
	if ( ! $file->isFile() || in_array( $file->getFilename(), $skip_files, true ) || ! preg_match( $extensions, $file->getFilename() ) ) {
		continue;
	}

	$path     = $file->getPathname();
	$original = (string) file_get_contents( $path );
	$content  = $original;

	// Theme URI and @link lines.
	if ( isset( $args['theme-uri'] ) ) {
		$content = str_replace( 'https://github.com/backdrop-dev/prologue', $args['theme-uri'], $content );
	} else {
		$content = preg_replace( '/^ \* Theme URI:[^\n]*\n/m', '', $content );
		$content = preg_replace( '/^ \* @link\s+https:\/\/github\.com\/backdrop-dev\/prologue[^\n]*\n/m', '', $content );
		$content = preg_replace( '/^\s*"homepage": "https:\/\/github\.com\/backdrop-dev\/prologue",\n/m', '', $content );
	}

	// The bundled resources keep their own authors: in readme.txt, only the
	// theme's copyright and its screenshot credit change hands.
	if ( 'readme.txt' === $file->getFilename() && false !== strpos( $content, '== Resources ==' ) ) {
		[ $head, $resources ] = explode( '== Resources ==', $content, 2 );
		$parts                = preg_split( '/(?=^Screenshot$)/m', $resources, 2 );
		$parts[1]             = isset( $parts[1] ) ? strtr( $parts[1], $author_replace ) : '';
		$content              = strtr( $head, $author_replace ) . '== Resources ==' . implode( '', $parts );
	} else {
		$content = strtr( $content, $author_replace );
	}
	$content = preg_replace( array_keys( $replace ), array_values( $replace ), $content );

	if ( $content !== $original ) {
		file_put_contents( $path, $content );
		$changed++;
	}
}

// Only the `@package` line in the tool scripts: the renamed theme's
// own files should not still claim `@package Prologue`.
foreach ( [ 'bin/rename.php', 'bin/package.php' ] as $tool ) {
	$tool_path = "{$root}/{$tool}";

	if ( ! is_file( $tool_path ) ) {
		continue;
	}

	$tool_content = (string) file_get_contents( $tool_path );
	$tool_content = str_replace( '@package Prologue', "@package {$namespace}", $tool_content );

	if ( $tool_content !== (string) file_get_contents( $tool_path ) ) {
		file_put_contents( $tool_path, $tool_content );
		$changed++;
	}
}

// The description, in style.css and as readme.txt's short description.
if ( isset( $args['description'] ) ) {
	$description = trim( $args['description'] );

	$style = (string) file_get_contents( "{$root}/style.css" );
	$style = preg_replace( '/^( \* Description:\s*).*$/m', '${1}' . addcslashes( $description, '\\$' ), $style );
	file_put_contents( "{$root}/style.css", $style );

	$readme = (string) file_get_contents( "{$root}/readme.txt" );
	$readme = preg_replace( '/(License URI:[^\n]*\n\n)[^\n]*\n/', '${1}' . addcslashes( mb_substr( $description, 0, 150 ), '\\$' ) . "\n", $readme, 1 );
	file_put_contents( "{$root}/readme.txt", $readme );
}

printf( "Renamed Prologue to %s in %d files.\n", $name, $changed );
printf( "  Slug and text domain: %s\n  Namespace: %s\n  Package: %s\n", $slug, $namespace, $package );

if ( basename( $root ) !== $slug ) {
	printf( "Rename the theme's folder from %s to %s.\n", basename( $root ), $slug );
}

echo "Next: run composer dump-autoload, replace screenshot.png, and review readme.txt.\n";
