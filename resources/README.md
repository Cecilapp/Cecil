# Resources

This folder contains the essential resources for the Cecil project:

## Folders

### `layouts/`

Twig template files used for generating static websites.

- `_default/` - Default templates (pages, lists, feeds, etc.)
- `extended/` - Extended templates (macros, themes)
- `partials/` - Reusable components (navigation, metatags, etc.)

### `server/`

Configuration files and scripts for the development server.

- `router.php` - PHP router for local server
- `livereload.js` - Script for automatic page reload

### `skeleton/`

Base structure for new Cecil projects.

- `cecil.yml` - Default configuration
- `pages/` - Sample content folder
- `layouts/` - Sample templates
- `assets/` - Sample static files

### `translations/`

Multilingual translation files.

- `messages.fr.po` - French messages (source)
- `messages.fr.mo` - French messages (compiled)

## Usage

Resources are integrated during project build and are accessible through the Twig rendering engine.
