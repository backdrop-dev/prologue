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

If you activate the theme before running `composer install`, it shows a notice in the admin instead of loading its PHP.

## Renaming

Replace these names throughout the theme, matching case:

| Find | Replace with | Where |
|---|---|---|
| `Prologue` | Your theme's name | `style.css` (`Theme Name`), `README.md`, pattern category label |
| `Prologue\` | Your PHP namespace | `functions.php`, `app/`, `composer.json` (`autoload`) |
| `prologue/` | Your prefix | pattern slugs in `patterns/` and `templates/`, hooks such as `prologue/bootstrap` |
| `'prologue'` | Your text domain | translation functions in `app/` and `patterns/`, `style.css` (`Text Domain`) |
| `prologue-` | Your prefix | stylesheet handles in `app/Assets/Assets.php` |
| `backdrop-dev/prologue` | Your package name | `composer.json` |

Then update the author, URIs and version in `style.css`, and run `composer dump-autoload`.

## What's Inside

```
app/                  PHP, organized as Backdrop service providers
  Assets/             Loads the global stylesheet and per-block stylesheets
  Blocks/             Registers block styles
  Patterns/           Registers pattern categories
parts/                Header and footer template parts
patterns/             Block patterns (registered automatically by WordPress)
public/css/style.css  Global CSS that theme.json can't express
public/css/blocks/    CSS for one block, loaded only when the block is used
styles/               Style variations
templates/            Block templates
theme.json            Colors, typography, spacing and layout
functions.php         Boots the Backdrop application
```

### Block styles

Block styles are the extra options in a block's **Styles** panel. Prologue adds **Card** for the Group block and **Pill** for the Button block. Add your own in `app/Blocks/BlockStyles.php`, or with the `prologue/block-styles` filter. Each style's `style_data` uses the same format as `theme.json`, so it follows the active style variation's colors.

### Per-block CSS

Add a file to `public/css/blocks/` named after the block, with the slash replaced by a dash. For example, `core-quote.css` styles the Quote block. It loads only on pages that use that block, in both the front end and the editor.

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

Set `WP_VERSION` to test another version, for example `WP_VERSION=6.6`. These tests run on every pull request, along with a PHP syntax check on PHP 7.4 to 8.5.

## License

Prologue is licensed under the [GNU General Public License v3.0 or later](LICENSE).
