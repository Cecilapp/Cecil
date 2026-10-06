<!--
title: "Filters"
description: "filter_by, markdown_to_html, toc, slugify, excerpt, highlight, preg_*, etc."
date: 2021-05-07
updated: 2026-10-05
-->
# Filters

Variables can be modified by [filters](https://twig.symfony.com/doc/filters/index.html). Filters are separated from the variable by a pipe symbol (`|`). Multiple filters can be chained. The output of one filter is applied to the next.

```twig
{{ page.title|truncate(25)|capitalize }}
```

## filter_by

Filters a pages collection by variable name/value.

```twig
{{ collection|filter_by(variable, value) }}
```

_Example:_

```twig
{{ pages|filter_by('section', 'blog') }}
```

## filter

For more complex cases, you should use [Twig’s native `filter`](https://twig.symfony.com/doc/filters/filter.html).

_Example:_

```twig
{% pages|filter(p => p.virtual == false and p.id not in ['page-1', 'page-2']) %}
```

## markdown_to_html

Converts a Markdown string to HTML.

```twig
{{ markdown|markdown_to_html }}
```

```twig
{% apply markdown_to_html %}
{# Markdown here #}
{% endapply %}
```

_Examples:_

```twig
{% set markdown = '**This is bold text**' %}
{{ markdown|markdown_to_html }}
```

```twig
{% apply markdown_to_html %}
**This is bold text**
{% endapply %}
```

## toc

Extracts only headings matching the given `selectors` (h2, h3, etc.), or those defined in config `pages.body.toc` if not specified.  
The `format` parameter defines the output format: `html` or `json`.  
The `url` parameter is used to build links to headings.

```twig
{{ markdown|toc(format, selectors, url) }}
```

_Examples:_

```twig
{{ page.body|toc }}
{{ page.body|toc('html') }}
{{ page.body|toc(selectors=['h2']) }}
{{ page.body|toc(url=url(page)) }}
```

## json_decode

Converts a JSON string to an array.

```twig
{{ json|json_decode }}
```

_Example:_

```twig
{% set json = '{"foo": "bar"}' %}
{% set array = json|json_decode %}
{{ array.foo }}
```

## yaml_parse

Converts a YAML string to an array.

```twig
{{ yaml|yaml_parse }}
```

_Example:_

```twig
{% set yaml = 'key: value' %}
{% set array = yaml|yaml_parse %}
{{ array.key }}
```

## slugify

Converts a string to a slug.

```twig
{{ string|slugify }}
```

## u

The `u` filter wraps a text in a Unicode object (a [Symfony UnicodeString instance](https://symfony.com/doc/current/components/string.html)) that exposes methods to "manipulate" the string.

_Example:_

```twig
{{ 'cecil_string with twig'|u.camel.title }}
```

> CecilStringWithTwig

## singular

The `singular` filter transforms a given noun in its plural form into its singular version.

```twig
{{ string|singular(locale)}}
```

_Example:_

```twig
{# English (en) rules are used by default #}
{{ 'partitions'|singular }}
```

> partition

```twig
{{ 'partitions'|singular('fr') }}
```

> partition

## plural

The `plural` filter transforms a given noun in its singular form into its plural version.

```twig
{{ string|plural(locale)}}
```

_Example:_

```twig
{# English (en) rules are used by default #}
{{ 'animal'|plural }}
```

> animals

```twig
{{ 'animal'|plural('fr') }}
```

> animaux

## excerpt

Truncates a string and appends suffix.

```twig
{{ string|excerpt(length, suffix) }}
```

| Option | Description                                | Type    | Default |
| ------ | ------------------------------------------ | ------- | ------- |
| length | Truncates after this number of characters. | integer | 450     |
| suffix | Appends characters.                        | string  | `…`     |

_Examples:_

```twig
{{ variable|excerpt }}
{{ variable|excerpt(250, '...') }}
```

## excerpt_html

Reads characters before or after `<!-- excerpt -->` or `<!-- break -->` tag.  
See [Content documentation](../../content/7-markdown.md#excerpt) for details.

```twig
{{ string|excerpt_html({separator, capture}) }}
```

| Option    | Description                                         | Type   | Default         |
| --------- | --------------------------------------------------- | ------ | --------------- |
| separator | String to use as separator.                         | string | `excerpt|break` |
| capture   | Part to capture, `before` or `after` the separator. | string | `before`        |

_Examples:_

```twig
{{ variable|excerpt_html }}
{{ variable|excerpt_html({separator: 'excerpt|break', capture: 'before'}) }}
{{ variable|excerpt_html({capture: 'after'}) }}
```

## highlight

Highlights a code string with [highlight.php](https://github.com/scrivo/highlight.php).

```twig
{{ code|highlight(language) }}
```

_Examples:_

```twig
{{ '<?php echo $highlighted->value; ?>'|highlight('php') }}
```

## preg_split

Splits a string into an array using a regular expression.

```twig
{{ string|preg_split(pattern, limit) }}
```

_Example:_

```twig
{% set headers = page.content|preg_split('/<br[^>]*>/') %}
```

## preg_match_all

Performs a regular expression match and return the group for all matches.

```twig
{{ string|preg_match_all(pattern, group) }}
```

_Example:_

```twig
{% set tags = page.content|preg_match_all('/<[^>]+>(.*)<\/[^>]+>/') %}
```

## hex_to_rgb

Converts a hexadecimal color to RGB.

```twig
{{ color|hex_to_rgb }}
```
