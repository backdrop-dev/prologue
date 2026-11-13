<?php
/**
 * Installs the test site, activates Prologue and adds test content.
 *
 * Usage: php tests/install.php /path/to/site
 *
 * @package Prologue
 */

$site = $argv[1] ?? '';

if ( ! $site || ! file_exists( "{$site}/wp-config.php" ) ) {
	fwrite( STDERR, "Usage: php tests/install.php /path/to/site\n" );
	exit( 1 );
}

define( 'WP_INSTALLING', true );
$_SERVER['HTTP_HOST'] = 'localhost';

require "{$site}/wp-load.php";
require_once ABSPATH . 'wp-admin/includes/upgrade.php';

// Don't try to send the welcome email.
add_filter( 'pre_wp_mail', '__return_false' );

wp_install( 'Prologue Test', 'admin', 'admin@example.test', true, '', wp_generate_password() );
update_option( 'permalink_structure', '/%postname%/' );

switch_theme( 'prologue' );

if ( get_stylesheet() !== 'prologue' ) {
	fwrite( STDERR, "Could not activate Prologue.\n" );
	exit( 1 );
}

$errors = wp_get_theme()->errors();

if ( $errors ) {
	fwrite( STDERR, 'Theme errors: ' . implode( ', ', $errors->get_error_messages() ) . "\n" );
	exit( 1 );
}

// A post with a comment, for the single template and the comments pattern.
$post_id = wp_insert_post( [
	'post_title'   => 'Test Post',
	'post_name'    => 'test-post',
	'post_content' => "<!-- wp:paragraph -->\n<p>Post body.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:quote -->\n<blockquote class=\"wp-block-quote\"><!-- wp:paragraph -->\n<p>A quote.</p>\n<!-- /wp:paragraph --></blockquote>\n<!-- /wp:quote -->",
	'post_status'  => 'publish',
] );

wp_insert_comment( [
	'comment_post_ID'  => $post_id,
	'comment_author'   => 'Commenter',
	'comment_content'  => 'Test comment.',
	'comment_approved' => 1,
] );

// A page that uses the theme's patterns and block styles.
wp_insert_post( [
	'post_type'    => 'page',
	'post_title'   => 'Patterns',
	'post_name'    => 'patterns',
	'post_content' => "<!-- wp:pattern {\"slug\":\"prologue/hero\"} /-->\n\n<!-- wp:pattern {\"slug\":\"prologue/call-to-action\"} /-->",
	'post_status'  => 'publish',
] );

// Save the rewrite rules for the new permalink structure.
flush_rewrite_rules();

printf( "Installed WordPress %s with %s %s.\n", $GLOBALS['wp_version'], wp_get_theme()->get( 'Name' ), wp_get_theme()->get( 'Version' ) );
