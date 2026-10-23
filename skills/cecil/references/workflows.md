# Common Workflows

## Create a Blog

1. Create `pages/blog/index.md` for blog section (`sortby`, `pagination` in its front matter if needed)
2. Add individual posts in `pages/blog/` (a date prefix sets the date: `2024-05-14_My post.md`), with an excerpt marker (`<!-- excerpt -->`) for lists
3. Declare the taxonomies (`taxonomies: {tags: tag}`) and set terms in the posts front matter (`tags: [PHP]`)
4. Optionally set permalinks (`pages.paths`, see [content.md](content.md#permalinks)) and list the posts on the homepage with `pagesfrom: blog` in `pages/index.md`
5. Rely on built-in `_default/list.html.twig` and `_default/page.html.twig`, or extend them in `layouts/blog/`
6. Feeds are generated for sections and terms (Atom by default; add RSS with `output.pagetypeformats.section: {rss: true}`)
7. Build with `php cecil.phar build`

## Add Custom Pages

1. Create markdown files in `pages/` directory
2. Add front matter with `title` and, if needed, `layout`
3. If needed, create a template in `layouts/` (preferably extending a built-in one, with `partials/metatags.html.twig` in `<head>`)
4. Let lookup rules pick the template, or set `layout: <name>` in front matter
5. Build to generate output

## Use Dynamic Content (Twig in Page Body)

By default, Twig syntax in a Markdown body is output as is. To evaluate it, create a dedicated layout and use it only for pages that need it:

```twig
{# layouts/dynamic.html.twig #}
{% extends '_default/page.html.twig' %}

{% block content %}
{{ include(template_from_string(page.content, "dynamic content for page " ~ page.id)) }}
{% endblock content %}
```

Then set `layout: dynamic` in the page front matter. The body can use `page.*`, `site.*`, functions, filters and Twig tags.

Caveats (Markdown is converted to HTML **before** Twig rendering):

- `<`, `>` and `=>` are escaped: comparisons and arrow functions fail (`Unexpected character "&"`)
- Twig expressions inside HTML attributes (e.g. `href="{{ url(post) }}"`) are URL-encoded and not evaluated
- An expression alone on its line is wrapped in `<p>`: wrap it in an HTML element separated by line breaks
- Twig in code spans/blocks **is** evaluated: use `{% verbatim %}` to display it as is

Keep the body simple and move complex logic into a partial template or a macro (see [Shortcodes](#shortcodes-with-macros)). See the [dynamic content documentation](https://cecil.app/documentation/content/dynamic-content/).

## Shortcodes with Macros

Twig macros can be used as shortcodes in the page body, through a layout that imports them before evaluating the content:

```twig
{# layouts/macros/shortcodes.html.twig #}
{% macro youtube(id, title = 'YouTube video') %}
<iframe src="https://www.youtube-nocookie.com/embed/{{ id }}" title="{{ title }}" width="560" height="315" loading="lazy" allowfullscreen></iframe>
{% endmacro %}
```

```twig
{# layouts/shortcodes.html.twig #}
{% extends '_default/page.html.twig' %}

{% block content %}
{% set imports = "{% import 'macros/shortcodes.html.twig' as sc %}" %}
{{ include(template_from_string(imports ~ page.content, "dynamic content for page " ~ page.id)) }}
{% endblock content %}
```

```markdown
---
layout: shortcodes
---
<div>
  {{ sc.youtube('NaB8JBfE7DY', 'Cecil demo') }}
</div>
```

For YouTube, Vimeo, Dailymotion or Gist links, the Markdown `{embed}` attribute is simpler (see [content.md](content.md#embeds-videos-audio-gists)); for reusable UI elements in templates, use [components](templates.md#components).

## Implement Search

The official `flexsearch` component theme provides a ready-to-use client-side search: prefer it, after asking the user. Otherwise:

1. Create `pages/search.md` with front matter `layout: search` and `output: json`
2. Use JavaScript library (e.g., Lunr.js) on frontend
3. Create `layouts/search.json.twig` that iterates `site.pages.showable` and emits a JSON array of `{title, url, content}` objects
4. Add search functionality to templates
