#!/usr/bin/env bash
#
# Serves the test site with PHP's built-in server and checks that Prologue's
# templates, patterns, block styles and stylesheets render.
#
# Usage: tests/run.sh /path/to/site
#
# Use the same SITE_URL as tests/setup.sh.

set -uo pipefail

WP_DIR="${1:?Usage: run.sh /path/to/site}"
SITE_URL="${SITE_URL:-http://127.0.0.1:8889}"
ADDRESS="${SITE_URL#http://}"
LOG="$WP_DIR/wp-content/debug.log"
OUT="$(mktemp -d)"
FAILED=0

rm -f "$LOG"
php -S "$ADDRESS" -t "$WP_DIR" > "$OUT/server.log" 2>&1 &
SERVER=$!
trap 'kill $SERVER 2>/dev/null; rm -rf "$OUT"' EXIT

for _ in $(seq 1 50); do
	curl -s -o /dev/null "$SITE_URL/" && break
	sleep 0.2
done

pass() { echo "PASS $1"; }
fail() { echo "FAIL $1"; FAILED=1; }

# fetch NAME PATH STATUS: saves the page as $OUT/NAME.html.
fetch() {
	local status
	status="$(curl -s -o "$OUT/$1.html" -w '%{http_code}' "$SITE_URL$2")"
	if [ "$status" = "$3" ]; then
		pass "$2 returns $3"
	else
		fail "$2 returns $3 (got $status)"
	fi
}

# expect NAME TEXT DESCRIPTION: checks that the page contains TEXT.
expect() {
	if grep -qF -- "$2" "$OUT/$1.html"; then
		pass "$3"
	else
		fail "$3 (missing: $2)"
	fi
}

fetch home / 200
expect home 'prologue-style-css' 'home: theme stylesheet is loaded'
expect home '<header class="wp-block-template-part">' 'home: header template part'
expect home '<footer class="wp-block-template-part">' 'home: footer template part'
expect home 'wp-block-site-title' 'home: site title'
if tr '\n' ' ' < "$OUT/home.html" | grep -qE '<header class="wp-block-template-part">\s*<div class="wp-block-group alignfull has-muted-background-color has-background'; then
	pass 'home: header has the muted background'
else
	fail 'home: header has the muted background'
fi
expect home 'Built with <a href="https://wordpress.org">WordPress</a> and Prologue.' 'home: footer pattern'
expect home 'wp-block-post-template' 'home: posts pattern'
expect home 'Test Post' 'home: lists the test post'
expect home '--wp--preset--color--primary: #2453b8' 'home: theme.json palette'

# Sticky posts.
if grep -qE '<li class="wp-block-post [^"]*\bsticky\b' "$OUT/home.html"; then
	pass 'home: sticky post has the sticky class'
else
	fail 'home: sticky post has the sticky class'
fi
expect home '<p class="prologue-sticky-label">Featured</p>' 'home: sticky post has the Featured label'
expect home 'prologue-core-post-template' 'home: post template stylesheet loads'
if [ "$(grep -o 'Featured Post\|Test Post' "$OUT/home.html" | head -n 1)" = 'Featured Post' ]; then
	pass 'home: sticky post is listed first'
else
	fail 'home: sticky post is listed first'
fi

fetch single /test-post/ 200
expect single 'wp-block-post-title' 'single: post title'
expect single 'Post body.' 'single: post content'
expect single 'wp-block-comments' 'single: comments pattern'
expect single 'Test comment.' 'single: shows the comment'
expect single 'prologue-core-quote' 'single: quote block stylesheet loads with the quote'
expect single 'prologue-core-post-comments-form' 'single: comment form stylesheet loads'
expect single 'prologue-core-comments' 'single: comments stylesheet loads'
expect single 'class="wp-block-group prologue-comment ' 'single: comments use the avatar layout'
expect single '<span class="prologue-author-label">Author</span>' 'single: post author comment has the Author label'
if [ "$(grep -o 'prologue-author-label">Author' "$OUT/single.html" | wc -l)" -eq 1 ]; then
	pass 'single: only the post author comment has the Author label'
else
	fail 'single: only the post author comment has the Author label'
fi

fetch page /patterns/ 200
expect page 'A fresh start for your next site' 'page: hero pattern'
expect page 'is-style-card' 'page: card block style is applied'
expect page 'is-style-pill' 'page: pill block style is applied'
expect page 'background-color: var(--wp--preset--color--muted);border-radius: 0.5rem' 'page: card block style CSS is generated'
expect page 'border-radius: 999px' 'page: pill block style CSS is generated'
if grep -qF 'prologue-core-quote' "$OUT/page.html"; then
	fail 'page: quote stylesheet should not load without a quote'
else
	pass 'page: quote stylesheet only loads where it is used'
fi

if grep -qF '<div class="wp-block-comments' "$OUT/page.html" || grep -qF 'id="respond"' "$OUT/page.html"; then
	fail 'page: no comments section when comments are off'
else
	pass 'page: no comments section when comments are off'
fi

fetch guestbook /guestbook/ 200
expect guestbook 'Guestbook comment.' 'page with comments on: shows its comments'
expect guestbook 'id="respond"' 'page with comments on: shows the comment form'

fetch archive /category/uncategorized/ 200
expect archive 'wp-block-query-title' 'archive: archive title'
expect archive 'Test Post' 'archive: lists the test post'

if grep -qF '<p class="prologue-sticky-label">' "$OUT/archive.html"; then
	fail 'archive: no Featured label outside the blog'
else
	pass 'archive: no Featured label outside the blog'
fi

fetch sticky /featured-post/ 200
if grep -qF '<p class="prologue-sticky-label">' "$OUT/sticky.html"; then
	fail 'single: no Featured label on the sticky post itself'
else
	pass 'single: no Featured label on the sticky post itself'
fi

fetch search '/?s=Test' 200
expect search 'wp-block-search' 'search: search form pattern'
expect search 'Test Post' 'search: finds the test post'

fetch nothing '/?s=zzzz-no-results' 200
expect nothing 'Nothing was found. Try a different search.' 'search: no-results message'

fetch missing /does-not-exist/ 404
expect missing 'Page not found' '404: page not found pattern'

# Block styles are registered.
if php -r '
	$_SERVER["HTTP_HOST"] = "localhost";
	require $argv[1] . "/wp-load.php";
	$registry = WP_Block_Styles_Registry::get_instance();
	exit( $registry->is_registered( "core/group", "card" ) && $registry->is_registered( "core/button", "pill" ) ? 0 : 1 );
' "$WP_DIR"; then
	pass 'block styles registered: core/group card, core/button pill'
else
	fail 'block styles registered: core/group card, core/button pill'
fi

# No PHP errors, warnings or notices.
if [ -s "$LOG" ]; then
	fail 'debug.log is empty'
	cat "$LOG"
else
	pass 'debug.log is empty'
fi

if [ "$FAILED" -ne 0 ]; then
	echo "Some checks failed."
	exit 1
fi

echo "All checks passed."
