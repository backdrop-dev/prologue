#!/usr/bin/env bash
#
# Sets up a WordPress site on SQLite with Prologue as the active theme.
#
# Usage: tests/setup.sh /path/to/site
#
# Run `composer install` in the theme first.
#
# Environment:
#   WP_VERSION      "latest" (default), a major version such as "6.6" (uses
#                   the newest 6.6.x release), or an exact version.
#   SITE_URL        Address the site will be served from. Defaults to
#                   http://127.0.0.1:8889.
#   SQLITE_VERSION  Tag of WordPress/sqlite-database-integration to use.

set -euo pipefail

WP_DIR="${1:?Usage: setup.sh /path/to/site}"
WP_VERSION="${WP_VERSION:-latest}"
SITE_URL="${SITE_URL:-http://127.0.0.1:8889}"
SQLITE_VERSION="${SQLITE_VERSION:-v3.1.1}"
THEME_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
REPO="https://github.com/WordPress/WordPress"

if [ -e "$WP_DIR" ]; then
	echo "$WP_DIR already exists. Remove it first." >&2
	exit 1
fi

if [ ! -f "$THEME_DIR/vendor/autoload.php" ]; then
	echo "Run composer install in the theme first." >&2
	exit 1
fi

# Resolve the version to a release tag.
TAGS="$(git ls-remote --tags --refs "$REPO" | awk -F/ '{print $3}' | grep -E '^[0-9]+\.[0-9]+(\.[0-9]+)?$' | sort -V)"

if [ "$WP_VERSION" = "latest" ]; then
	TAG="$(echo "$TAGS" | tail -n 1)"
elif [[ "$WP_VERSION" =~ ^[0-9]+\.[0-9]+$ ]]; then
	TAG="$(echo "$TAGS" | grep -E "^${WP_VERSION//./\\.}(\.[0-9]+)?$" | tail -n 1)"
else
	TAG="$WP_VERSION"
fi

if [ -z "$TAG" ]; then
	echo "No WordPress release found for $WP_VERSION." >&2
	exit 1
fi

echo "Installing WordPress $TAG into $WP_DIR"
git -c advice.detachedHead=false clone --quiet --depth 1 --branch "$TAG" "$REPO" "$WP_DIR"

# Install the SQLite database drop-in. Newer releases keep the plugin in a
# package that links to a sibling package, so copy it with links resolved.
SQLITE_SRC="$(mktemp -d)"
git -c advice.detachedHead=false clone --quiet --depth 1 --branch "$SQLITE_VERSION" https://github.com/WordPress/sqlite-database-integration "$SQLITE_SRC"
SQLITE_DIR="$WP_DIR/wp-content/plugins/sqlite-database-integration"
if [ -d "$SQLITE_SRC/packages/plugin-sqlite-database-integration" ]; then
	cp -RL "$SQLITE_SRC/packages/plugin-sqlite-database-integration" "$SQLITE_DIR"
else
	cp -R "$SQLITE_SRC" "$SQLITE_DIR"
	rm -rf "$SQLITE_DIR/.git"
fi
rm -rf "$SQLITE_SRC"
sed -e "s#{SQLITE_IMPLEMENTATION_FOLDER_PATH}#$SQLITE_DIR#" \
	-e "s#{SQLITE_PLUGIN}#sqlite-database-integration/load.php#" \
	"$SQLITE_DIR/db.copy" > "$WP_DIR/wp-content/db.php"

cat > "$WP_DIR/wp-config.php" <<PHP
<?php
define( 'DB_NAME', 'wordpress' );
define( 'DB_USER', '' );
define( 'DB_PASSWORD', '' );
define( 'DB_HOST', '' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );
define( 'DB_DIR', __DIR__ . '/wp-content/database/' );
define( 'DB_FILE', '.ht.sqlite' );
\$table_prefix = 'wp_';
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
define( 'WP_HOME', '$SITE_URL' );
define( 'WP_SITEURL', '$SITE_URL' );
define( 'AUTOMATIC_UPDATER_DISABLED', true );
define( 'WP_HTTP_BLOCK_EXTERNAL', true );
define( 'DISABLE_WP_CRON', true );
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
require_once ABSPATH . 'wp-settings.php';
PHP

# Copy the theme, without the parts a release leaves out.
mkdir -p "$WP_DIR/wp-content/themes/prologue"
tar -C "$THEME_DIR" --exclude=.git --exclude=.github --exclude=tests --exclude=node_modules -cf - . | tar -C "$WP_DIR/wp-content/themes/prologue" -xf -

php "$THEME_DIR/tests/install.php" "$WP_DIR"
