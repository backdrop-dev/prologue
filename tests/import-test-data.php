<?php
/**
 * Imports WXR files into the test site with the WordPress Importer plugin.
 *
 * Usage: php tests/import-test-data.php /path/to/site file.xml [file.xml ...]
 *
 * @package Prologue
 */

$site  = $argv[1] ?? '';
$files = array_slice( $argv, 2 );

if ( ! $site || ! $files ) {
	fwrite( STDERR, "Usage: php tests/import-test-data.php /path/to/site file.xml [file.xml ...]\n" );
	exit( 1 );
}

define( 'WP_ADMIN', true );
define( 'WP_LOAD_IMPORTERS', true );
$_SERVER['HTTP_HOST'] = 'localhost';

require "{$site}/wp-load.php";
require_once ABSPATH . 'wp-admin/includes/admin.php';

wp_set_current_user( 1 );

// Don't download attachments: the test site may have no network access.
add_filter( 'import_allow_fetch_attachments', '__return_false' );

require_once WP_PLUGIN_DIR . '/wordpress-importer/wordpress-importer.php';

foreach ( $files as $file ) {
	$importer                    = new WP_Import();
	$importer->fetch_attachments = false;

	ob_start();
	$importer->import( $file );
	$output = ob_get_clean();

	if ( false !== strpos( $output, 'Sorry, there has been an error' ) ) {
		fwrite( STDERR, "Could not import {$file}:\n" . wp_strip_all_tags( $output ) . "\n" );
		exit( 1 );
	}

	printf( "Imported %s\n", basename( $file ) );
}

// The importer suspends cache invalidation; make sure rewrite rules and
// counts are fresh for the crawl.
flush_rewrite_rules();

$counts = wp_count_posts( 'post' );
$pages  = wp_count_posts( 'page' );

printf(
	"Site now has %d published posts, %d published pages, %d categories and %d tags.\n",
	$counts->publish,
	$pages->publish,
	wp_count_terms( [ 'taxonomy' => 'category' ] ),
	wp_count_terms( [ 'taxonomy' => 'post_tag' ] )
);
