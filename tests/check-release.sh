#!/usr/bin/env bash
#
# Builds the release zip, installs it on a test site made by tests/setup.sh,
# and runs WordPress.org's Theme Check plugin against it.
#
# Usage: tests/check-release.sh /path/to/site
#
# Run tests/run.sh or tests/crawl.sh afterwards to check the installed zip
# renders. The site keeps the zip's copy of the theme.
#
# Environment:
#   THEME_CHECK_REF  Tag of WordPress/theme-check to use.

set -euo pipefail

WP_DIR="${1:?Usage: check-release.sh /path/to/site}"
THEME_CHECK_REF="${THEME_CHECK_REF:-20260901}"
THEME_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SLUG="$(sed -n 's/^[ \t\/*#@]*Text Domain:[ \t]*\(.*\)$/\1/p' "$THEME_DIR/style.css" | head -n 1 | tr -d '[:space:]')"
VERSION="$(sed -n 's/^[ \t\/*#@]*Version:[ \t]*\(.*\)$/\1/p' "$THEME_DIR/style.css" | head -n 1 | tr -d '[:space:]')"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

if [ ! -f "$WP_DIR/wp-config.php" ]; then
	echo "No site at $WP_DIR. Run tests/setup.sh first." >&2
	exit 1
fi

php "$THEME_DIR/bin/package.php" "$TMP/dist"

rm -rf "$WP_DIR/wp-content/themes/$SLUG"
unzip -q "$TMP/dist/$SLUG-$VERSION.zip" -d "$WP_DIR/wp-content/themes/"
echo "Installed $SLUG-$VERSION.zip on the test site."

git -c advice.detachedHead=false clone --quiet --depth 1 --branch "$THEME_CHECK_REF" https://github.com/WordPress/theme-check "$TMP/theme-check"

php "$THEME_DIR/tests/theme-check.php" "$WP_DIR" "$TMP/theme-check" "$SLUG"
