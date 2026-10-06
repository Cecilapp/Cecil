<!--
title: "Templates"
description: "Work with Twig layouts, templates and components."
date: 2021-05-07
updated: 2026-10-05
weight: 3
sortby: weight
alias: documentation/layouts
-->
# Templates

Cecil is powered by the [Twig](https://twig.symfony.com) template engine, so please refer to the **[official documentation](https://twig.symfony.com/doc/templates.html)** to learn how to use it.

## Example

```twig
{# this is a template example #}
<h1>{{ page.title }} - {{ site.title }}</h1>
<span>{{ page.date|date('j M Y') }}</span>
<p>{{ page.content }}</p>
<ul>
{% for tag in page.tags %}
  <li>{{ tag }}</li>
{% endfor %}
</ul>
```

- `{# #}`: adds comments
- `{{ }}`: outputs content of variables or expressions
- `{% %}`: executes statements, like loop (`for`), condition (`if`), etc.
- `|filter()`: filters or formats content
