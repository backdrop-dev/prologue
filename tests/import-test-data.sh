#!/usr/bin/env bash
#
# Imports WordPress's Theme Test Data into a test site made by tests/setup.sh.
#
# Usage: tests/import-test-data.sh /path/to/site
#
# Imports both files from https://github.com/WordPress/theme-test-data:
# themeunittestdata.wordpress.xml (posts, pages, menus and edge cases such as
# long titles, nested comments and paginated posts) and 64-block-test-data.xml
# (a post for each core block). Attachments aren't downloaded, so images in
# the content point at the original external URLs.
#
# Environment:
#   TEST_DATA_REF  Commit or branch of theme-test-data. Defaults to a fixed
#                  commit so results don't change unexpectedly.
#   IMPORTER_REF   Tag of WordPress/wordpress-importer.

set -euo pipefail

WP_DIR="${1:?Usage: import-test-data.sh /path/to/site}"
TEST_DATA_REF="${TEST_DATA_REF:-b47acf980696897936265182cb684dca648476c7}"
IMPORTER_REF="${IMPORTER_REF:-0.9.7}"
THEME_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

if [ ! -f "$WP_DIR/wp-config.php" ]; then
	echo "No site at $WP_DIR. Run tests/setup.sh first." >&2
	exit 1
fi

# theme-test-data has no tags, so fetch the commit directly.
git init --quiet "$TMP/data"
git -C "$TMP/data" fetch --quiet --depth 1 https://github.com/WordPress/theme-test-data "$TEST_DATA_REF"
git -C "$TMP/data" -c advice.detachedHead=false checkout --quiet FETCH_HEAD

git -c advice.detachedHead=false clone --quiet --depth 1 --branch "$IMPORTER_REF" https://github.com/WordPress/wordpress-importer "$TMP/importer"
rm -rf "$WP_DIR/wp-content/plugins/wordpress-importer"
cp -R "$TMP/importer/src" "$WP_DIR/wp-content/plugins/wordpress-importer"

php "$THEME_DIR/tests/import-test-data.php" "$WP_DIR" \
	"$TMP/data/themeunittestdata.wordpress.xml" \
	"$TMP/data/64-block-test-data.xml"
