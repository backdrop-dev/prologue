<?php
/**
 * Prints the paths to crawl on the test site, one per line.
 *
 * Covers the blog and its second page, every published post and page,
 * the second page of paginated posts, every category, tag, post format and
 * author archive, each month and year with posts, search and a 404.
 *
 * Usage: php tests/urls.php /path/to/site
 *
 * @package Prologue
 */

$_SERVER['HTTP_HOST'] = 'localhost';

require ( $argv[1] ?? '' ) . '/wp-load.php';

$home  = untrailingslashit( home_url() );
$paths = [ '/', '/page/2/', '/?s=content', '/?s=zzzz-no-results' ];

$posts = get_posts( [
	'post_type'      => [ 'post', 'page' ],
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'orderby'        => 'ID',
	'order'          => 'ASC',
] );

foreach ( $posts as $post ) {
	$paths[] = get_permalink( $post );

	if ( false !== strpos( $post->post_content, '<!--nextpage-->' ) ) {
		$paths[] = trailingslashit( get_permalink( $post ) ) . '2/';
	}

	// Date archives only list posts.
	if ( 'post' === $post->post_type ) {
		$paths[] = get_month_link( get_the_date( 'Y', $post ), get_the_date( 'm', $post ) );
		$paths[] = get_year_link( get_the_date( 'Y', $post ) );
	}
}

foreach ( [ 'category', 'post_tag', 'post_format' ] as $taxonomy ) {
	$terms = get_terms( [ 'taxonomy' => $taxonomy, 'hide_empty' => true ] );

	foreach ( is_array( $terms ) ? $terms : [] as $term ) {
		$paths[] = get_term_link( $term );
	}
}

foreach ( get_users( [ 'has_published_posts' => [ 'post' ] ] ) as $user ) {
	$paths[] = get_author_posts_url( $user->ID );
}

foreach ( array_unique( $paths ) as $path ) {
	echo str_replace( $home, '', $path ), "\n";
}
