# Layouts

This directory contains built-in Twig layout templates provided by Cecil.

## Structure

`_default/`
: Default layout templates used as the primary fallback during rendering. These templates provide the core page structures (single page, list, taxonomy, etc.) when no more specific template is selected.

`partials/`
: Reusable template fragments included by full layouts. This is the preferred place for shared UI building blocks such as headers, footers, metadata blocks, navigation pieces, and repeated markup patterns.

`extended/`
: Optional and advanced layout templates used for custom rendering scenarios. This folder is typically used to host alternative template variants or feature-specific layouts without changing the default baseline.

## Details

### `_default/`

- `404.html.twig`: Basic 404 error page ("Page not found").
- `404.json.twig`: JSON variant of the 404 response.
- `feed.xsl.twig`: XSL stylesheet used to render XML feeds (Atom/RSS) in browsers.
- `home.html.twig`: Homepage layout, used by lookup fallback for home pages.
- `list.atom.twig`: Atom feed template.
- `list.html.twig`: List of pages, with optional pagination.
- `list.json.twig`: JSON representation of a list of pages.
- `list.jsonfeed.twig`: JSON Feed format template.
- `list.rss.twig`: RSS feed template.
- `page.embed.twig`: Embedded page rendering for integration contexts.
- `page.html.twig`: Main default page template with a clean built-in CSS baseline.
- `page.json.twig`: JSON representation of a single page.
- `page.oembed.twig`: oEmbed response template for embeddable content.
- `redirect.html.twig`: Redirect page template.
- `robots.txt.twig` : `robots.txt` template allowing pages except 404 and referencing the sitemap.
- `sitemap.xml.twig`: `sitemap.xml` template listing pages.
- `sitemap.xsl.twig` : XSL stylesheet used to render the sitemap XML in browsers.
- `vocabulary.html.twig` : Simple list of all terms in a vocabulary.

### `partials/`

- `alternates-languages.html.twig`: Language alternate links (`hreflang`) for multilingual pages.
- `alternates.html.twig`: Alternate links (canonical and format alternates).
- `data.json.twig`: Reusable JSON serialization fragment for page/list data.
- `feeds-from-section.html.twig`: Links to section feeds (Atom, RSS, JSON Feed).
- `googleanalytics.js.twig`: Google Analytics integration snippet.
- `jsonld.js.twig`: Structured data output in JSON-LD format.
- `languages.html.twig`: Basic language switcher.
- `metatags.html.twig`: Centralized metatags template (title, description, canonical, Open Graph, Twitter card, etc.).
- `navigation.html.twig`: Main menu navigation.
- `new.css.twig`: Built-in CSS fragment used by default templates.
- `page-navigation.html.twig`: Previous/next navigation between pages.
- `paginator.html.twig`: Simple paginated navigation for list templates.
- `terms-list.html.twig`: Terms list rendering helper for taxonomy-related outputs.
