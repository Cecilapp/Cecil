# My website

This website is built with [Cecil](https://cecil.app), a static site generator.

## Usage

Preview the website locally (with live reload):

```bash
cecil serve
```

Build the website (in the `_site/` directory):

```bash
cecil build
```

Create a new page:

```bash
cecil new:page
```

> [!TIP]
> If you use the Phar file, replace `cecil` with `php cecil.phar`.

## Structure

- `cecil.yml`: configuration
- `pages/`: content, written in Markdown
- `layouts/`: Twig templates (overrides the built-in ones)
- `assets/`: images, styles and scripts, processed by Cecil
- `data/`: data files (YAML, JSON, CSV, etc.)
- `static/`: files copied as is

## Documentation

<https://cecil.app/documentation/>
