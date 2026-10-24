---
title: Templates
description: How to customize the templates, styles and data of the website.
---
# Templates

The [Twig](https://twig.symfony.com/) templates of the `layouts/` directory override the built-in ones.

## Main template

`layouts/page.html.twig` extends the built-in template and overrides some of its blocks:

- `head_css`: adds the `assets/css/style.scss` stylesheet, compiled by Cecil
- `footer`: adds the links listed in the `data/links.yaml` data file

```twig
{% extends '_default/page.html.twig' %}

{%- block head_css ~%}
  {{- parent() ~}}
  {{ css(asset('css/style.scss')) }}
{%- endblock head_css ~%}
```

## Section templates

The pages of a section can use their own templates, stored in a directory named after the section:

- `layouts/blog/list.html.twig`: the list of posts
- `layouts/blog/page.html.twig`: a post
- `layouts/docs/page.html.twig`: a page of the documentation, with a table of contents

## Data files

The files of the `data/` directory are available in templates through the `site.data` variable:

```twig
{% for link in site.data.links %}
  <a href="{{ link.url }}">{{ link.name }}</a>
{% endfor %}
```

## Learn more

See the [templates documentation](https://cecil.app/documentation/templates/).
