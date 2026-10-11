# Resources

This folder contains the internal resources bundled with Cecil (and embedded in the PHAR).

## Contents

| Path | Description | Used by |
| --- | --- | --- |
| [`layouts/`](layouts/README.md) | Built-in Twig templates | Renderer (layouts fallback) |
| [`server/`](server/README.md) | Local development server helpers | `serve` command |
| [`skeleton/`](skeleton/README.md) | Starter site structure | `new:site` command |
| [`translations/`](translations/README.md) | Built-in translation catalogs | Renderer (translator) |
| `icon.png` | Cecil icon | Desktop notifications |

### `layouts/`

Built-in Twig templates, used as the last fallback when a layout is not found in the site `layouts/` directory or in a theme.

- `_default/`: default templates (home, page, list, term, vocabulary, feeds, sitemap, `robots.txt`, `llms.txt`, 404, redirect, etc.) in several output formats (HTML, JSON, Markdown, Atom, RSS, JSON Feed, oEmbed, etc.)
- `partials/`: reusable fragments (metatags, navigation, breadcrumb, paginator, alternates, JSON-LD, etc.)
- `extended/`: base templates extended by other templates (e.g.: `feed.twig`)
- `shortcodes.twig`: built-in shortcodes macros (`youtube`, `gist`)

Templates can be copied into the site with the `util:templates:extract` command.

### `server/`

Helper files for the local development server.

- `router.php`: router for the PHP built-in server
- `livereload.js`: script injected into HTML pages to automatically reload the browser on changes

### `skeleton/`

Base structure used to create a new site.

- `cecil.yml`: default configuration
- `assets/`: sample assets (icon, favicon)
- `layouts/`: sample templates (blog section)
- `pages/`: sample content (home, about, blog posts, section pages)
- `static/`: static files copied as-is

### `translations/`

Built-in translation catalogs for templates messages.

- `messages.fr.po`: French catalog (source)
- `messages.fr.mo`: French catalog (compiled)

## Lookup order

When rendering, Cecil looks for layouts and translations in the following order:

1. site (`layouts/` and `translations/` directories)
2. theme(s) (`themes/<theme>/layouts/` and `themes/<theme>/translations/`)
3. Cecil internal resources (this folder)
