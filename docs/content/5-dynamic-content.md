<!--
title: "Dynamic content"
description: "Use variables and Twig expressions inside page content."
date: 2021-05-07
updated: 2026-10-09
-->
# Dynamic content

By default, the body of a page is static: Twig syntax written in a Markdown file is output as is.

You can create dynamic content in a page by using the [`template_from_string`](https://twig.symfony.com/doc/3.x/functions/template_from_string.html) Twig function, which renders the page content as a Twig template.

```twig
{{ include(template_from_string(page.content, "dynamic content for page " ~ page.id)) }}
```

With this, you can use any page variable in the _body_ of the page.

```twig
---
var: 'value'
---
The value of `var` is {{ page.var }}.
```

## Enable dynamic content

The recommended way is to create a dedicated layout that overrides the `content` block, and to use it only for pages that need it.

```twig
{# layouts/dynamic.html.twig #}
{% extends '_default/page.html.twig' %}

{% block content %}
{{ include(template_from_string(page.content, "dynamic content for page " ~ page.id)) }}
{% endblock content %}
```

Then set the [`layout`](2-front-matter.md#predefined-variables) variable in the front matter of the page:

```yaml
---
title: "My dynamic page"
layout: dynamic
---
```

:::tip
The second argument of `template_from_string` is the name of the template: it is displayed in error messages, so including `page.id` helps to find the page in error.
:::

## Available variables

The page content is rendered with the same context as the layout, so you can use:

- page variables: `page.title`, `page.date`, `page.<custom variable>`, etc.
- site variables: `site.title`, `site.pages`, [`site.data`](../templates/2-variables.md#site-data), etc.
- all [functions](../templates/reference/1-functions.md), [filters](../templates/reference/3-filters.md) and [sorts](../templates/reference/2-sorts.md) available in templates;
- Twig tags: `{% set %}`, `{% if %}`, `{% for %}`, `{% include %}`, etc.

## How it works

The Markdown body is **converted to HTML first**, then the result (`page.content`) is rendered by Twig. This order has some consequences:

- Markdown escapes the `<` and `>` characters, so comparison operators (`>`, `<=`) and arrow functions (`=>`) can’t be used in the body: the build fails with an `Unexpected character "&"` error.
- A Twig expression used in an HTML attribute (e.g. `href="{{ url(post) }}"`) is URL encoded by the Markdown converter and won’t be evaluated.
- A Twig expression alone on its own line is wrapped in a `<p>` element.
- Twig syntax written inside a code span or a code block **is** evaluated too.

:::tip
Keep the body simple (variables, filters, short conditions) and move complex markup and logic into a [partial template](#example-include-a-partial-template) or a [macro](#example-shortcodes-with-macros).
:::

To display Twig syntax as is, wrap it in a `verbatim` tag:

```twig
{% verbatim %}`{{ page.title }}`{% endverbatim %}
```

To avoid an extra `<p>` element around a block of HTML, wrap the expression in an HTML element, separated by line breaks:

```twig
<div>
{{ include('partials/latest-posts.html.twig') }}
</div>
```

## Example: variables, data and partial template {#example-include-a-partial-template}

This example shows a page displaying site and page variables, data from a [data file](../templates/2-variables.md#site-data), and the latest blog posts through a reusable partial template.

Data file:

```yaml
# data/team.yml
- name: Alice
  role: Developer
- name: Bob
  role: Designer
```

Partial template, which receives `section` and `limit` as parameters:

```twig
{# layouts/partials/latest-posts.html.twig #}
{% set posts = site.pages.showable|filter_by('section', section|default('blog'))|sort_by_date|slice(0, limit|default(5)) %}
{% if posts|length > 0 %}
<ul class="latest-posts">
  {% for post in posts %}
  <li><a href="{{ url(post) }}">{{ post.title }}</a> <time datetime="{{ post.date|date('Y-m-d') }}">{{ post.date|format_date('long') }}</time></li>
  {% endfor %}
</ul>
{% else %}
<p>No posts yet.</p>
{% endif %}
```

Page:

```markdown
---
title: About
layout: dynamic
---
Welcome to **{{ site.title }}**! This page was published on {{ page.date|format_date('long') }}.

Our team has {{ site.data.team|length }} members: {{ site.data.team|column('name')|join(', ') }}.

## Latest posts

<div>
{{ include('partials/latest-posts.html.twig', {section: 'blog', limit: 3}) }}
</div>
```

Output:

```html
<p>Welcome to <strong>My site</strong>! This page was published on October 9, 2026.</p>
<p>Our team has 2 members: Alice, Bob.</p>
<h2 id="latest-posts">Latest posts</h2>
<div>
<ul class="latest-posts">
  <li><a href="/blog/post-3/">Post 3</a> <time datetime="2026-03-01">March 1, 2026</time></li>
  <li><a href="/blog/post-2/">Post 2</a> <time datetime="2026-02-01">February 1, 2026</time></li>
  <li><a href="/blog/post-1/">Post 1</a> <time datetime="2026-01-01">January 1, 2026</time></li>
</ul>
</div>
```

:::info
The `>` operator and the `href="{{ … }}"` attribute work here because they are written in the partial template, not in the Markdown body.
:::

## Example: shortcodes with macros {#example-shortcodes-with-macros}

[Twig macros](https://twig.symfony.com/doc/3.x/tags/macro.html) can be used as _shortcodes_ to insert rich HTML snippets in the body of a page.

Create the macros:

```twig
{# layouts/macros/shortcodes.html.twig #}
{% macro youtube(id, title = 'YouTube video') %}
<iframe src="https://www.youtube-nocookie.com/embed/{{ id }}" title="{{ title }}" width="560" height="315" loading="lazy" allowfullscreen></iframe>
{% endmacro %}

{% macro alert(message, type = 'info') %}
<div class="alert alert-{{ type }}" role="alert">{{ message }}</div>
{% endmacro %}
```

Create a layout that imports the macros before rendering the content:

```twig
{# layouts/shortcodes.html.twig #}
{% extends '_default/page.html.twig' %}

{% block content %}
{% set imports = "{% import 'macros/shortcodes.html.twig' as sc %}" %}
{{ include(template_from_string(imports ~ page.content, "dynamic content for page " ~ page.id)) }}
{% endblock content %}
```

Use the shortcodes in a page:

```markdown
---
title: "Demo video"
layout: shortcodes
---
<div>
  {{ sc.alert('This video is in English.', 'warning') }}
</div>

## Demo

<div>
{{ sc.youtube('NaB8JBfE7DY', 'Cecil demo') }}
</div>
```

Output:

```html
<div>
  <div class="alert alert-warning" role="alert">This video is in English.</div>
</div>
<h2 id="demo">Demo</h2>
<div>
  <iframe src="https://www.youtube-nocookie.com/embed/NaB8JBfE7DY" title="Cecil demo" width="560" height="315" loading="lazy" allowfullscreen></iframe>
</div>
```

:::tip
To reuse UI elements across templates and pages, see also [components](../templates/4-components.md).
:::
