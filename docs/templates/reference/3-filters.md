<!--
title: "Filters"
description: "filter_by, markdown_to_html, toc, slugify, excerpt, highlight, preg_*, etc."
date: 2021-05-07
updated: 2026-10-18
-->
# Filters

Variables can be modified by [filters](https://twig.symfony.com/doc/filters/index.html). Filters are separated from the variable by a pipe symbol (`|`). Multiple filters can be chained. The output of one filter is applied to the next.

```twig
{{ page.title|truncate(25)|capitalize }}
```

:::info
See also: filters dedicated to assets (`resize`, `minify`, `fingerprint`, `to_css`, `inline`, `webp`, `lqip`, etc.) are documented in the [Assets](../../assets/index.md) section.
:::

## filter_by

Filters a pages collection by variable name/value.

```twig
{{ collection|filter_by(variable, value) }}
```

Only regular pages (type `page`, not virtual) are returned.

_Example:_

```twig
{{ pages|filter_by('section', 'blog') }}
```

## filter

For more complex cases, you should use [Twig’s native `filter`](https://twig.symfony.com/doc/filters/filter.html).

_Example:_

```twig
{% set filtered = pages|filter(p => p.virtual == false and p.id not in ['page-1', 'page-2']) %}
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
The `format` parameter defines the output format: `html` (default) or `json`.  
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
{{ string|singular(locale) }}
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
{{ string|plural(locale) }}
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

Truncates a string and appends a suffix.

```twig
{{ string|excerpt(length, suffix) }}
```

| Option | Description                                | Type    | Default                        |
| ------ | ------------------------------------------ | ------- | ------------------------------ |
| length | Truncates after this number of characters. | integer | 450                            |
| suffix | Appends characters.                        | string  | `' …'` (space and ellipsis)    |

_Examples:_

```twig
{{ variable|excerpt }}
{{ variable|excerpt(250, '...') }}
```

## excerpt_html

Reads characters before or after `<!-- excerpt -->` or `<!-- break -->` tag.  
See [Content documentation](../../content/3-markdown.md#excerpt) for details.

```twig
{{ string|excerpt_html({separator, capture}) }}
```

| Option    | Description                                         | Type   | Default          |
| --------- | --------------------------------------------------- | ------ | ---------------- |
| separator | String to use as separator.                         | string | `excerpt|break`  |
| capture   | Part to capture, `before` or `after` the separator. | string | `before`         |

Default values are defined by the `pages.body.excerpt` configuration.

_Examples:_

```twig
{{ variable|excerpt_html }}
{{ variable|excerpt_html({separator: 'excerpt|break', capture: 'before'}) }}
{{ variable|excerpt_html({capture: 'after'}) }}
```

## highlight

Highlights a code string.

```twig
{{ code|highlight(language, engine) }}
```

The highlighter engine (`highlightjs` or `tempest`) is the optional `engine` argument or, by default, the [`layouts.highlight.engine`](../../configuration/7-layouts.md#layouts-highlight) option (`highlightjs` by default).

:::tip
The filter does not use the [`pages.body.highlight.engine`](../../configuration/4-pages.md#pages-body-highlight) option, which only applies to Markdown content.
:::

If the language is not supported by the engine, the code is returned escaped, without highlighting (and a warning is logged).

_Examples:_

```twig
{{ '<?php echo $highlighted->value; ?>'|highlight('php') }}
{{ '<?php echo $highlighted->value; ?>'|highlight('php', 'tempest') }}
```

:::info
Each engine uses its own CSS classes (`hljs-*` for `highlightjs`, `hl-*` for `tempest`): the stylesheet must match the engine used.
:::

## preg_split

Splits a string into an array using a regular expression (`limit` is `0`, i.e. no limit, by default).

```twig
{{ string|preg_split(pattern, limit) }}
```

_Example:_

```twig
{% set headers = page.content|preg_split('/<br[^>]*>/') %}
```

## preg_match_all

Performs a regular expression match and returns the group for all matches (`group` is `0`, i.e. the full match, by default).

```twig
{{ string|preg_match_all(pattern, group) }}
```

_Example:_

```twig
{% set tags = page.content|preg_match_all('/<[^>]+>(.*)<\/[^>]+>/') %}
```

## hex_to_rgb

Converts a hexadecimal color to RGB: returns an array with `red`, `green` and `blue` keys.

```twig
{{ color|hex_to_rgb }}
```

_Example:_

```twig
{% set rgb = '#f00'|hex_to_rgb %}
rgb({{ rgb.red }}, {{ rgb.green }}, {{ rgb.blue }})
```

> rgb(255, 0, 0)

## splitline

Splits a string into an array of lines of `max` characters maximum (`18` by default), without breaking words.

```twig
{{ string|splitline(max) }}
```

_Example:_

```twig
{% for line in 'The quick brown fox jumps'|splitline(10) %}
  {{ line }}<br>
{% endfor %}
```

> The quick<br>brown fox<br>jumps

## iterable

Converts a variable to an iterable (array): an array is returned as is, a traversable object is converted to an array, and any other value is wrapped in an array.

```twig
{{ variable|iterable }}
```

_Example:_

```twig
{% for author in page.authors|iterable %}
  {{ author }}
{% endfor %}
```

## unique

Removes duplicate values from an array of strings (case-insensitive: the first occurrence is kept).

```twig
{{ array|unique }}
```

_Example:_

```twig
{{ ['PHP', 'php', 'Twig']|unique|join(', ') }}
```

> PHP, Twig

## duration_to_iso8601

Converts a duration in seconds to an [ISO 8601 duration](https://en.wikipedia.org/wiki/ISO_8601#Durations) (e.g.: for the `duration` of a video in structured data).

```twig
{{ seconds|duration_to_iso8601 }}
```

_Example:_

```twig
{{ 125|duration_to_iso8601 }}
```

> PT00H00M125S
