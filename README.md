# Prologue

A clean block starter theme for WordPress, built on [Backdrop 2](https://github.com/backdrop-dev/framework). Copy it, rename it, and make it your own.

Prologue gives you a working block theme to start from: `theme.json`, templates, template parts, patterns and a style variation. The small amount of PHP a block theme needs is organized into Backdrop service providers.

## Requirements

- WordPress 6.6 or later
- PHP 7.4 or later
- [Composer](https://getcomposer.org)

## Getting Started

1. Copy the theme into `wp-content/themes/` under your theme's folder name.
2. Rename it (see below).
3. Run `composer install` in the theme folder.
4. Activate the theme under **Appearance → Themes**.

If you activate the theme before running `composer install`, it shows a dismissible notice in the admin instead of loading its PHP.

## Renaming

`bin/rename.php` replaces Prologue's name, slug, prefixes, namespace and author throughout the theme, including its tests:

```bash
php bin/rename.php "Harbor Light" \
	--author="Your Name" \
	--author-uri=https://example.com \
	--theme-uri=https://example.com/harbor-light \
	--contributor=your-wordpress-org-username \
	--description="A calm block theme for writers."
composer dump-autoload
```

The slug (`harbor-light`, used as the folder name, text domain and prefix), the namespace (`HarborLight`) and the Composer package name are worked out from the name; set them with `--slug`, `--namespace` and `--package`. Leave out `--theme-uri` to drop the Theme URI. The script refuses names WordPress.org doesn't allow, such as ones containing "Theme" or "WordPress".

Then rename the theme's folder to the slug, replace `screenshot.png`, and review `readme.txt`.

## Publishing on WordPress.org

Prologue itself isn't submitted to the theme directory: its name is already taken there. A theme made from it is built to pass the [theme review requirements](https://make.wordpress.org/themes/handbook/review/required/):

- `readme.txt` in the directory's format, with a Copyright section and a Resources section that credits the bundled Backdrop packages and Composer autoloader. Add any fonts or images you bundle.
- A 1200×900 `screenshot.png` of the theme's own patterns. Replace it with one of your theme.
- No plugin-territory features, nothing loaded from other sites, every string translatable, and every function, hook, handle and class prefixed with the slug.

Build the zip to upload with:

```bash
php bin/package.php
```

It writes `dist/{slug}-{version}.zip`, with the Composer dependencies installed (reviewers and users won't run Composer) and without development files: `tests/`, `bin/`, `README.md`, the Composer files and anything hidden. It stops if the zip would contain a file WordPress.org doesn't allow.

To run WordPress.org's [Theme Check](https://github.com/WordPress/theme-check) against that zip on a test site:

```bash
tests/setup.sh /tmp/theme-site
tests/check-release.sh /tmp/theme-site
```

Before you submit, also check that the name isn't already used in the theme directory, and that the version, changelog and "Tested up to" are current in both `style.css` and `readme.txt`.

## What's Inside

```
app/                  PHP, organized as Backdrop service providers
  Assets/             Loads the global stylesheet and per-block stylesheets
  Blocks/             Block styles, sticky posts, author comments, page links
  Patterns/           Registers pattern categories
parts/                Header and footer template parts
patterns/             Block patterns (registered automatically by WordPress)
public/css/style.css  Global CSS that theme.json can't express
public/css/blocks/    CSS for one block, loaded only when the block is used
styles/               Style variations
templates/            Block templates
theme.json            Colors, typography, spacing and layout
functions.php         Boots the Backdrop application
readme.txt            Readme for WordPress.org, with copyright and resources
screenshot.png        1200×900 screenshot for the Themes screen
bin/                  rename.php and package.php (left out of the zip)
tests/                Site tests, Theme Test Data crawl, Theme Check
```

### Block styles

Block styles are the extra options in a block's **Styles** panel. Prologue adds **Card** for the Group block and **Pill** for the Button block. Add your own in `app/Blocks/BlockStyles.php`, or with the `prologue/block-styles` filter. Each style's `style_data` uses the same format as `theme.json`, so it follows the active style variation's colors.

### Per-block CSS

Add a file to `public/css/blocks/` named after the block, with the slash replaced by a dash. For example, `core-quote.css` styles the Quote block. It loads only on pages that use that block, in both the front end and the editor.

### Sticky posts

On the first page of the blog, WordPress lists sticky posts first and adds the `sticky` class to them. Prologue styles them as a highlighted card in `public/css/blocks/core-post-template.css`, and `app/Blocks/StickyPosts.php` adds a translatable **Featured** label above the title. Change the label with the `prologue/sticky-label` filter, or return an empty string to remove it.

### Forms

Text fields, text areas and selects share one look in `public/css/style.css`, using the palette so they follow the style variation. That covers the comment form, the Search block, the password form on protected posts, and forms from plugins. The comment form's layout (name and email side by side on wider screens, the consent checkbox, notes and labels) is in `public/css/blocks/core-post-comments-form.css`.

### Comments

The comments list (`patterns/comments.php`) shows each commenter's avatar beside their name, date, comment and reply link. `public/css/blocks/core-comments.css` separates the section from the post, divides comments with thin lines, indents replies with a line on the left. Comments by the post's author stand out with a tinted card, a ring around the avatar and an **Author** label after the name, added by `app/Blocks/AuthorComments.php`. Change the label with the `prologue/comment-author-label` filter, or return an empty string to remove it.

### Split posts

When a post is split into pages with the Page Break block, `app/Blocks/PageLinks.php` adds "Pages: 1 2 3" links after its content. Block themes don't get these links from WordPress.

### Patterns

Files in `patterns/` are registered by WordPress. Patterns with `Inserter: no` (`posts`, `comments`, `search-form`, `404`) are used by the templates and keep their text translatable.

### Adding PHP

Add a component class that implements `Backdrop\Contracts\Bootable`, a service provider that registers and boots it, and register the provider in `functions.php`. Child themes and plugins can add providers on the `prologue/bootstrap` action, which receives the application.

## Testing

`tests/setup.sh` installs WordPress on SQLite with Prologue active, and `tests/run.sh` serves the site with PHP's built-in server and checks that each template, pattern, block style and stylesheet renders without PHP errors.

```bash
composer install
tests/setup.sh /tmp/prologue-site
tests/run.sh /tmp/prologue-site
```

Set `WP_VERSION` to test another version, for example `WP_VERSION=6.6`.

To test against WordPress's [Theme Test Data](https://github.com/WordPress/theme-test-data), import it into the same site and crawl every page:

```bash
tests/import-test-data.sh /tmp/prologue-site
tests/crawl.sh /tmp/prologue-site
```

The import adds about 120 posts and 24 pages full of edge cases: long titles and words, nested comments, split and password-protected posts, classic and block content. The crawl requests every post, page and archive (about 340 pages) and checks each returns the right status with its header and footer, no unrendered blocks and no PHP errors.

All of these run on every pull request, along with a PHP syntax check on PHP 7.4 to 8.5. CI also builds the release zip and runs Theme Check against it, and renames a copy of Prologue with `bin/rename.php` and runs every check on that copy, so a theme made from Prologue keeps passing.

## License

Prologue is licensed under the [GNU General Public License v3.0 or later](LICENSE).
