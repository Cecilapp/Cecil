<!--
title: "Functions"
description: "url, html, readtime, hash, cache_key, getenv, dump, etc."
date: 2021-05-07
updated: 2026-10-05
-->
# Functions

> [Functions](https://twig.symfony.com/doc/functions/index.html) can be called to generate content. Functions are called by their name followed by parentheses (`()`) and may have arguments.

## url

Creates a valid URL for a page, a menu entry, an asset, a page ID or a path.

```twig
{{ url(value, {options}) }}
```

| Option    | Description                                                                                                                      | Type    | Default |
| --------- | -------------------------------------------------------------------------------------------------------------------------------- | ------- | ------- |
| canonical | Prefix URL with [`baseurl`](../../configuration/1-site.md#baseurl) or use [`canonical.url`](../../configuration/1-site.md#metatags-options) if exists. | boolean | `false` |
| format    | Defines page [output format](../../configuration/8-output.md#output-formats) (e.g.: `json`).                                                  | string  | `html`  |
| language  | Defines page [language](../../configuration/2-languages.md#language) (e.g.: `fr`).                                                               | string  | null    |

_Examples:_

```twig
{# page #}
{{ url(page) }}
{{ url(page, {canonical: true}) }}
{{ url(page, {format: json}) }}
{{ url(page, {language: fr}) }}
{# menu entry #}
{{ url(site.menus.main.about) }}
{# asset #}
{{ url(asset('styles.css')) }}
{# page ID #}
{{ url('page-id') }}
{# path #}
{{ url('about-me/') }}
{{ url('tags/' ~ tag) }}
```

:::info
For convenience the `url` function is also available as a filter:

```twig
{# page #}
{{ page|url }}
{{ page|url({canonical: true, format: json, language: fr}) }}
{# asset #}
{{ asset('styles.css')|url }}
```

:::

:::tip
When the value is a string, `url()` slugifies it to find a matching page ID (e.g.: `url('tags/My Tag')` returns the URL of the page `tags/my-tag`). If no page matches, the string is kept as a path, with invalid characters (e.g.: spaces) percent-encoded.
:::

## html

Creates an HTML element from an asset (or an array of assets with custom attributes).

```twig
{{ html(asset, {attributes}, {options}) }}
{# dedicated functions for each common type of asset #}
{{ css(asset) }}
{{ js(asset) }}
{{ image(asset) }}
{{ audio(asset) }}
{{ video(asset) }}
```

| Option     | Description                                                                                                                                                                                    | Type  |
| ---------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----- |
| attributes | Adds `name="value"` couple to the HTML element.                                                                                                                                                | array |
| options    | `{preload: boolean}`: preloads.<br>For images:<br>`{formats: array}`: adds alternative formats.<br>`{responsive: bool|string}`: adds responsive images (based on `width` or pixels `density`).<br>`{placeholder: string}`: fills the image background before loading (`color` or `lqip`). | array |

:::warning
Since version ++8.42.0++, the `html` function replace the deprecated `html` filter.
:::

:::tip
You can define a global default behavior of images options (`formats`, `responsive` and `placeholder`) through the [layouts configuration](../../configuration/7-layouts.md#layouts-images).

When [`layouts.images.dark_suffix`](../../configuration/7-layouts.md#layouts-images) is configured (e.g. `.dark`), Cecil automatically looks for a dark variant of each image (e.g. `photo.dark.jpg` alongside `photo.jpg`) and generates a `<picture>` element with a `<source media="(prefers-color-scheme: dark)">`.

In the same way, when [`layouts.images.mobile_suffix`](../../configuration/7-layouts.md#layouts-images) is configured (e.g. `.mobile`), Cecil looks for a mobile variant of each image (e.g. `photo.mobile.jpg`) and adds a `<source>` with the [`layouts.images.mobile_media_query`](../../configuration/7-layouts.md#layouts-images) media query. If a dark variant of the mobile image exists (e.g. `photo.mobile.dark.jpg`), it is used on mobile with dark color scheme.
:::

_Examples:_

```twig
{# CSS with an attribute #}
{{ html(asset('print.css'), {media: 'print'}) }}
{# CSS with an attribute and an option #}
{{ html(asset('styles.css'), {title: 'Main theme'}, {preload: true}) }}
{# Array of assets with media query #}
{{ html([
  {asset: asset('css/style.css')},
  {asset: asset('css/style-dark.css'), attributes: {media: '(prefers-color-scheme: dark)'}}
]) }}
{# JavaScript #}
{{ html(asset('script.js')) }}
{# image without specific attributes nor options #}
{{ html(asset('image.png')) }}
{# image with specific attributes, responsive images and alternative formats #}
{{ html(asset('image.jpg'), {alt: 'Description', loading: 'lazy'}, {responsive: true, formats: ['avif', 'webp']}) }}
{# image with responsive pixels density images #}
{{ html(asset('image.jpg'), options={responsive: 'density'}, attributes={width: 256}) }}
{# image with a Low-Quality Image Placeholder #}
{{ html(asset('image.jpg'), {alt: 'Description', loading: 'lazy'}, {placeholder: 'lqip'}) }}
{# Audio #}
{{ html(asset('audio.mp3')) }}
{# Video #}
{{ html(asset('video.mp4')) }}
```

:::info
For convenience the `html` function stay available as a filter (but is considered as deprecated):

```twig
{{ asset|html({attributes}, {options}) }}
```

:::

## readtime

Determines read time of a text, in minutes.

```twig
{{ readtime(value) }}
```

_Example:_

```twig
{{ readtime(page.content) }} min
```

## hash

Calculates the hash of an object, an array or a string with a given algorithm.

```twig
{{ hash(value, algorithm) }}
```

`algorithm` can be any algorithm supported by PHP's `hash()` function (e.g.: `md5`, `sha256`, etc.). Default is `xxh128`.

_Example:_

```twig
{{ hash('my string', 'sha256') }}
```

## cache_key

Calculates a cache key for [_fragments_ cache](../6-cache.md#fragments-cache) based on a name and an optional value.

```twig
{% cache cache_key(name, value) %}
  {# cacheable content #}
{% endcache %}
```

The function adds a hash of the value (could be a string, an array or an object) to the name (and the current language and build ID to be sure the generated cache key is unique) so if the value is changed the cache key is changed too and the cache is automatically cleared.

## getenv

Gets the value of an environment variable from its key.

```twig
{{ getenv(var) }}
```

_Example:_

```twig
{{ getenv('VAR') }}
```

## dump

The `dump` function dumps information about a template variable. This is mostly useful to debug a template that does not behave as expected by introspecting its variables:

```twig
{{ dump(user) }}
```

:::important
The [_debug mode_](../../configuration/1-site.md#debug) must be enabled.
:::

## d

The `d()` function is the HTML version of [`dump()`](#dump) and use the [Symfony VarDumper Component](https://symfony.com/doc/5.4/components/var_dumper.html) behind the scenes.

```twig
{{ d(variable, {theme: light}) }}
```

- If _variable_ is not provided then the function returns the current Twig context
- Available themes are « light » (default) and « dark »

:::important
The [_debug mode_](../../configuration/1-site.md#debug) must be enabled.
:::
