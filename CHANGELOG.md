# Changelog

All notable changes to Prologue are documented here. The format is
based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

### Fixed

- Blog home now has an H1; the hero pattern no longer creates a
  duplicate H1, and its buttons (and the call-to-action button) are
  real, focusable links.
- `bin/package.php` and `bin/rename.php` (and the test scripts) only
  run from the command line.
- Post navigation has an accessible name; links keep their underline
  on hover.
- `color-mix()` styles have plain-colour fallbacks for older browsers.
- Front-end layout CSS no longer leaks into the editor.

### Added

- `templates/front-page.html` and `templates/home.html`.
- Explicit spacing presets in `theme.json`, so the theme does not rely
  on WordPress core's default spacing scale.
- PHPCS, PHPStan, PHPUnit and theme.json schema validation in CI and
  via Composer scripts; WP 7.1 / PHP 8.5 in the CI matrix.
- `.editorconfig`, `CONTRIBUTING.md`, `languages/prologue.pot`, and
  `phpcs.xml`.
- `bin/package.php` now fails if `style.css` Version and `readme.txt`
  Stable tag disagree.

## [1.0.0] - 2026-11-15

Initial release.

### Added

- Clean block starter theme for WordPress, built on Backdrop 2.
- `theme.json`, templates, template parts, patterns and a dark style
  variation.
- Block styles (Card for Group, Pill for Button), sticky-post labels,
  author-comment labels and split-post page links.
- `bin/rename.php` to rename the theme, and `bin/package.php` to
  build a WordPress.org-ready release zip.
- Site tests, Theme Test Data crawl and Theme Check in CI.
