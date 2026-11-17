# Contributing to Prologue

Thanks for helping improve Prologue. This is a starter theme, so keep
changes small, focused, and useful to a theme built from it.

## Workflow

1. Branch from `develop` and open a pull request against `develop`.
2. Keep the theme passing every check in CI: PHP syntax on PHP 7.4–8.5,
   the WordPress site tests, the release zip + Theme Check, and the
   renamed-clone checks.
3. If you change a template, pattern or `theme.json`, add or update a
   check in `tests/run.sh` or `tests/crawl.sh` where one fits.

## Before you push

```bash
composer install
composer lint
composer validate-theme-json
composer phpcs
composer phpstan
composer phpunit
```

`composer phpcs` uses the WordPress Coding Standards (`phpcs.xml`).
`composer phpstan` runs PHPStan at level 5. `composer phpunit` runs the
unit tests in `tests/Unit/`; the WordPress site tests are in
`tests/run.sh` and `tests/crawl.sh` and need a test site first:

```bash
tests/setup.sh /tmp/prologue-site
tests/run.sh /tmp/prologue-site
```

Check for outdated dependencies with `composer outdated --direct`.

## Translations

Strings are in the `prologue` text domain. Regenerate the template
after changing a translatable string:

```bash
wp i18n make-pot . languages/prologue.pot
```

The theme loads its text domain on `after_setup_theme`
(`app/Assets/Assets.php`), and ships `languages/prologue.pot`.

## Naming

Every function, hook, handle, class, text domain and CSS class is
prefixed with the theme slug (`prologue`). Template-part and custom
template titles in `theme.json` cannot carry a text domain — that is a
WordPress limitation, not an oversight; see the README.

## Releasing

Bump the version in `style.css` and the Stable tag in `readme.txt`
together — `bin/package.php` fails the build if they differ — and add
an entry to `CHANGELOG.md` and `readme.txt`.
