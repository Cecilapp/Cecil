# Packaging

Files needed to publish a theme as a Composer package, and an optional demo site.


## composer.json

Themes are installed with Composer thanks to `cecil/theme-installer` (2.1+), which copies the package to `themes/<theme-name>`:

```json
{
  "name": "cecil/theme-<name>",
  "type": "cecil-theme",
  "description": "Cecil <Name> theme",
  "keywords": ["Cecil", "theme", "<name>"],
  "license": "MIT",
  "require": {
    "cecil/theme-installer": "^2.1"
  },
  "extra": {
    "cecil": {
      "theme-name": "<name>"
    }
  },
  "minimum-stability": "dev",
  "prefer-stable": true,
  "config": {
    "allow-plugins": {
      "cecil/theme-installer": true
    }
  }
}
```

- The theme name is resolved in this order: `extra.cecil.theme-name`, then `extra.name` (legacy, still used by the official themes and still supported), then the package name without the `theme-`/`cecil-theme-` prefix or `-theme` suffix (`vendor/theme-foo` → `foo`). Always set `extra.cecil.theme-name` explicitly: it must match the name used in `theme`.
- The theme name may only contain letters, digits, `.`, `_` and `-`, and must start with a letter or a digit; otherwise the installation fails.
- `extra.cecil.theme-name` requires `cecil/theme-installer` `^2.1` (PHP 8.1+, Composer 2): older installers only read `extra.name` and fail without it.
- `config.allow-plugins` here only serves the theme's own development; the Cecil **project** must allow the plugin too (see the README outline below).
- Use `"description": "Cecil component theme <Name>"` for a component theme.
- Add a branch alias for the development branch, e.g. `"extra": {"cecil": {"theme-name": "<name>"}, "branch-alias": {"dev-main": "1.x-dev"}}`.
- Declare dependent themes in `require` (e.g. `"cecil/theme-fontawesome": "^1.5"`) **and** tell the user to add them to `theme`.
- Tailwind themes can add a builder:

  ```json
  "require-dev": { "aligny/tailwind-builder": "^1.0" },
  "scripts": {
    "css:build": "tailwind-builder ./assets/css/tailwind.css --output=./assets/styles.css --minify",
    "css:watch": "tailwind-builder ./assets/css/tailwind.css --output=./assets/styles.css --watch"
  }
  ```

## .editorconfig

```ini
root = true

[*]
indent_style = space
indent_size = 2
end_of_line = lf
charset = utf-8
trim_trailing_whitespace = true
insert_final_newline = true

[*.twig]
insert_final_newline = false
```

## README.md

Follow this outline:

````markdown
# <Name> theme

The _<Name>_ theme for [Cecil](https://cecil.app) is …

![Demo screenshot](docs/screenshot.png)

## Features

## Installation

Allow the theme installer plugin in the `composer.json` file of your Cecil project (required since Composer 2.2):

```json
{
  "config": {
    "allow-plugins": {
      "cecil/theme-installer": true
    }
  }
}
```

Then require the theme:

```bash
composer require cecil/theme-<name>
```

> Or [download the latest archive](https://github.com/<vendor>/theme-<name>/releases/latest/) and uncompress its content in `themes/<name>`.

## Usage

Add `<name>` in the `theme` section of your configuration file (`cecil.yml`):

```yaml
theme:
  - <name>
```

### Configuration

(namespaced options with their default values)

### Internationalization

## License

_<Name>_ is a free software distributed under the terms of the MIT license.
````

For a component theme, also document the line to include in layouts (partial or macro import).

## .gitattributes

Keep development files out of the release archive:

```ini
# Files to exclude when creating archive
.* export-ignore
/demo export-ignore
/docs export-ignore

# Auto detect text files and perform LF normalization
* text=auto
```

## Demo site (recommended)

Recent official themes (e.g. `flexsearch`, `darkmodetoggle`) ship a `demo/` website in the repository:

- `demo/config.yml` enables the theme (`theme: [<name>]`) and sets its options; `demo/pages/` and a minimal `demo/layouts/_default/page.html.twig` show the feature.
- `demo/link.php` links the repository as `demo/themes/<name>` (symlink `../..`, or `mklink /J` junction on Windows), so the demo always uses the theme under development.
- `demo/.gitignore` ignores `_site/`, `.cache/`, `.cecil/` and `themes/`.

Run it locally:

```bash
php demo/link.php
php cecil.phar serve demo
```

A `.github/workflows/demo.yml` workflow deploys it to GitHub Pages: set `CECIL_BASEURL: https://<vendor>.github.io/theme-<name>/`, download `cecil.phar`, run `php demo/link.php`, `php cecil.phar build demo -vv`, then upload `demo/_site` with `actions/upload-pages-artifact` and deploy with `actions/deploy-pages`. Link the demo from the README (`**[Demo](https://<vendor>.github.io/theme-<name>/)**`).
