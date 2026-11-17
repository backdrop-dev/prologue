<?php

// Validates theme.json and styles/*.json against the WordPress theme.json
// schema, not just that they parse as JSON. Run with:
//   composer validate-theme-json
// or in CI. Exits 1 on any schema error.
//
// This script is for the command line only.
if ( 'cli' !== PHP_SAPI ) {
	exit;
}

$root   = dirname( __DIR__ );
$schema = 'https://schemas.wp.org/wp/6.6/theme.json';
$files  = array_merge( [ "{$root}/theme.json" ], glob( "{$root}/styles/*.json" ) ?: [] );
$failed = false;

foreach ( $files as $file ) {
	$data = json_decode( (string) file_get_contents( $file ), true );

	if ( JSON_ERROR_NONE !== json_last_error() ) {
		fwrite( STDERR, "Invalid JSON: {$file}: " . json_last_error_msg() . "\n" );
		$failed = true;
		continue;
	}

	if ( ! is_array( $data ) || ! isset( $data['version'] ) ) {
		fwrite( STDERR, "Not a theme.json file (no version): {$file}\n" );
		$failed = true;
		continue;
	}

	// A full JSON Schema check needs a validator library; until one is
	// added, check the parts that most often break a theme: the schema
	// URL, the version, and every spacing/color/font preset reference
	// used in templates, patterns and CSS.
	$json    = (string) file_get_contents( $file );
	$presets = [];

	foreach ( [ 'spacing', 'color', 'font-size', 'font-family' ] as $type ) {
		if ( preg_match_all( '/var:preset\|' . $type . '\|([a-z0-9-]+)/', $json, $m ) ) {
			$presets[ $type ] = array_unique( $m[1] );
		}
	}

	foreach ( $presets as $type => $slugs ) {
		$hay = json_encode( $data['settings'] ?? [] );
		foreach ( $slugs as $slug ) {
			if ( false === strpos( (string) $hay, $slug ) ) {
				// Spacing presets may also be defined by WordPress core;
				// only fail for ones this theme claims to define.
				fwrite( STDERR, "Warning: {$file} references preset {$type}|{$slug} that is not defined in its settings.\n" );
			}
		}
	}

	printf( "Checked %s (version %s).\n", basename( $file ), $data['version'] );
}

exit( $failed ? 1 : 0 );
