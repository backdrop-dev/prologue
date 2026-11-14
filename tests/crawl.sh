#!/usr/bin/env bash
#
# Requests every page of the test site and checks each one renders.
#
# Usage: tests/crawl.sh /path/to/site
#
# Meant to run after tests/import-test-data.sh. For each path from
# tests/urls.php, checks the response is 200 (404 for the missing page),
# the page has the header and footer, no block was left unrendered, and
# WordPress didn't show its critical error page. Then checks a few edge
# cases from the test data, and that debug.log has no PHP errors, warnings
# or notices.
#
# Use the same SITE_URL as tests/setup.sh.

set -uo pipefail

WP_DIR="${1:?Usage: crawl.sh /path/to/site}"
SITE_URL="${SITE_URL:-http://127.0.0.1:8889}"
ADDRESS="${SITE_URL#http://}"
THEME_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
LOG="$WP_DIR/wp-content/debug.log"
OUT="$(mktemp -d)"
FAILED=0
COUNT=0

php -S "$ADDRESS" -t "$WP_DIR" > "$OUT/server.log" 2>&1 &
SERVER=$!
trap 'kill $SERVER 2>/dev/null; rm -rf "$OUT"' EXIT

for _ in $(seq 1 50); do
	curl -s -o /dev/null "$SITE_URL/" && break
	sleep 0.2
done

fail() { echo "FAIL $1"; FAILED=1; }

check() {
	local path="$1" expected="$2" status
	COUNT=$((COUNT + 1))
	status="$(curl -s -o "$OUT/page.html" -w '%{http_code}' "$SITE_URL$path")"

	if [ "$status" != "$expected" ]; then
		fail "$path returns $expected (got $status)"
		return
	fi

	grep -qF '<header class="wp-block-template-part">' "$OUT/page.html" || fail "$path: missing header"
	grep -qF '<footer class="wp-block-template-part">' "$OUT/page.html" || fail "$path: missing footer"
	grep -qF 'critical error on this website' "$OUT/page.html" && fail "$path: critical error"
	grep -qE '<!-- /?wp:' "$OUT/page.html" && fail "$path: unrendered block markup"
}

rm -f "$LOG"
PATHS="$(php "$THEME_DIR/tests/urls.php" "$WP_DIR")"

while IFS= read -r path; do
	check "$path" 200
done <<< "$PATHS"

check /does-not-exist-at-all/ 404

# Edge cases from the test data that need something from the theme.

# expect PATH TEXT DESCRIPTION
expect() {
	curl -s -o "$OUT/page.html" "$SITE_URL$1"
	grep -qF -- "$2" "$OUT/page.html" || fail "$3 (missing on $1: $2)"
}

expect /template-paginated/ 'class="prologue-page-links"' 'split post shows page links'
expect /template-paginated/2/ 'class="prologue-page-links"' 'split post shows page links on page 2'
expect / '<p class="prologue-sticky-label">Featured</p><h2 class="wp-block-post-title' 'sticky test post shows the Featured label'
expect /template-password-protected/ 'post-password-form' 'password-protected post shows the password form'

if [ -s "$LOG" ]; then
	fail "debug.log has PHP errors:"
	sort "$LOG" | uniq -c | sort -rn | head -n 30
fi

if [ "$FAILED" -ne 0 ]; then
	echo "Checked $COUNT pages. Some checks failed."
	exit 1
fi

echo "Checked $COUNT pages. All passed."
