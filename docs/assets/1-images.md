<!--
title: "Images"
description: "Resize, crop and convert images, generate responsive images and placeholders."
date: 2021-05-07
updated: 2026-10-10
-->
# Images

Cecil provides Twig functions and filters to resize, crop and convert images, and to generate responsive images and placeholders.

## image_srcset

Builds the HTML img `srcset` (responsive) attribute of an image Asset.

```twig
{{ image_srcset(asset) }}
```

_Examples:_

```twig
{% set asset = asset(image_path) %}
<img src="{{ url(asset) }}" width="{{ asset.width }}" height="{{ asset.height }}" alt="" class="asset" srcset="{{ image_srcset(asset) }}" sizes="{{ image_sizes('asset') }}">
```

## image_sizes

Returns the HTML img `sizes` attribute based on a CSS class name.  
It should be use in conjunction with the [`image_srcset`](1-images.md#image-srcset) function.

```twig
{{ image_sizes('class') }}
```

_Examples:_

```twig
{% set asset = asset(image_path) %}
<img src="{{ url(asset) }}" width="{{ asset.width }}" height="{{ asset.height }}" alt="" class="asset" srcset="{{ image_srcset(asset) }}" sizes="{{ image_sizes('asset') }}">
```

## image_from_website

Builds the HTML img element from a website URL by extracting its illustration image.
Returns `null` if no image is found.

```twig
{{ image_from_website('url', {attributes}, {options}) }}
```

The image is searched in the page HTML with the following fallbacks, the first candidate that can be downloaded as an image is used:

1. Open Graph: `og:image:secure_url`, `og:image`, `og:image:url`
2. Twitter: `twitter:image`, `twitter:image:src`
3. `<link rel="image_src">`
4. Microdata: `itemprop="image"`
5. JSON-LD: `image` property
6. First `<img>` of `<article>`, `<main>` or `<body>`
7. `<link rel="apple-touch-icon">`
8. `<link rel="icon">`

Relative URLs are resolved against `<base href>` or the page URL.

The resolved image URL and the downloaded image are cached (see [`cache.assets.remote.ttl`](../configuration/9-cache.md)).

Options:

- `fallback`: image path (or URL) used if no image is found
- other [`image`](../templates/reference/1-functions.md#html) options (e.g.: `responsive`, `formats`)

_Examples:_

```twig
{{ image_from_website('https://example.com/page-with-image.html') }}

{# with a fallback image #}
{{ image_from_website('https://example.com/', {alt: 'Illustration'}, {fallback: 'images/default.png'}) }}
```

## resize

Resizes an image to a specified width (in pixels) or/and height (in pixels).

- If only the width is specified, the height is calculated to preserve the aspect ratio
- If only the height is specified, the width is calculated to preserve the aspect ratio
- If both width and height are specified, the image is resized to fit within the given dimensions, image is cropped and centered if necessary
- If remove_animation is true, any animation in the image (e.g., GIF) will be removed

```twig
{{ asset(image_path)|resize(width: width, height: height, remove_animation: bool) }}
```

:::info
The original file is not altered and the resized version is saved at `/thumbnails/<width>x<height>/image.jpg`.
:::

:::tip
ICO files are supported: the largest icon is resized and saved as a single icon ICO file (PNG compressed). Icons stored as BMP with a color depth other than 24 or 32 bits require the [Imagick](https://www.php.net/manual/book.imagick.php) PHP extension: otherwise, the original ICO file is kept and a warning is logged.
:::

_Examples:_

```twig
{{ asset(page.image)|resize(300) }}
{# equivalent to: #}
{{ asset(page.image)|resize(width: 300) }}
{# resizes to 300px width, height auto-calculated to preserve aspect ratio #}
{{ asset(page.image)|resize(height: 200) }}
{# resizes to 300px width and 200px height, and crops if necessary #}
{{ asset(page.image)|resize(300, 200) }}
{# removes any animation from the image #}
{{ asset(page.image)|resize(width: 1200, height: 630, remove_animation: true) }}
```

## cover

Resizes an image to a specified width and height, cropping it if necessary.

:::warning
The `cover` filter is deprecated since version ++8.77++ and will be removed in future versions. Use the [`resize`](#resize) filter instead, with both width and height parameters.
:::

```twig
{{ asset(image_path)|cover(width, height) }}
```

_Example:_

```twig
{{ asset(page.image)|cover(1200, 630) }}
```

## maskable

Adds padding, in pourcentages, to an image to make it maskable.

```twig
{{ asset(image_path)|maskable(padding) }}
```

_Example:_

```twig
{{ asset('icon.png')|maskable }}
```

## webp

Converts an image to [WebP](https://developers.google.com/speed/webp) format.

_Example:_

```twig
<picture>
    <source type="image/webp" srcset="{{ asset(image_path)|webp }}">
    <img src="{{ url(asset(image_path)) }}" width="{{ asset(image_path).width }}" height="{{ asset(image_path).height }}" alt="">
</picture>
```

## avif

Converts an image to [AVIF](https://github.com/AOMediaCodec/libavif) format.

_Example:_

```twig
<picture>
    <source type="image/avif" srcset="{{ asset(image_path)|avif }}">
    <img src="{{ url(asset(image_path)) }}" width="{{ asset(image_path).width }}" height="{{ asset(image_path).height }}" alt="">
</picture>
```

## lqip

Returns a [Low Quality Image Placeholder](https://www.guypo.com/introducing-lqip-low-quality-image-placeholders) (100x100 px, 50% blurred) as data URL.

```twig
{{ asset(image_path)|lqip }}
```

## dominant_color

Returns the dominant [hexadecimal color](https://developer.mozilla.org/en-US/docs/Web/CSS/hex-color) of an image.

```twig
{{ asset(image_path)|dominant_color }}
```
