<!--
title: "Assets"
description: "Handle assets (images, stylesheets, scripts, etc.) with the asset() function: process, optimize and fingerprint them."
date: 2021-05-07
updated: 2026-10-16
weight: 4
sortby: weight
-->
# Assets

Assets are files (stylesheets, scripts, images, fonts, etc.) that Cecil can process and optimize from your templates.

## asset

An asset is a resource usable in templates, like CSS, JavaScript, image, audio, video, etc.

The `asset()` function creates an _asset_ object from a file path, an array of file paths (bundle) or a URL (remote file). The asset is then processed (compiled, minified, fingerprinted, etc.) according to the [configuration](../configuration/6-assets.md).

Files are looked up in the assets directory (`assets/` by default), then in the static directory (`static/` by default) and in the themes.

```twig
{{ asset(path, {options}) }}
```

| Option         | Description                                                                                                        | Type    | Default                                          |
| -------------- | ------------------------------------------------------------------------------------------------------------------ | ------- | ------------------------------------------------ |
| filename       | Saves a bundle to a custom file name.                                                                              | string  | `styles.css` or `scripts.js` (bundle)            |
| ignore_missing | Does not stop the build if the file is not found.                                                                  | boolean | `false`                                          |
| fingerprint    | Adds the content hash to the file name.                                                                            | boolean | value of `assets.fingerprint` (`true`)           |
| minify         | Minifies CSS or JavaScript.                                                                                        | boolean | value of `assets.minify` (`true`)                |
| optimize       | Optimizes an image.                                                                                                | boolean | value of `assets.images.optimize` (`false`)      |
| fallback       | Local asset to load if the remote file is not found.                                                               | string  | empty                                            |
| useragent      | User agent string sent when fetching a remote file (see [assets configuration](../configuration/6-assets.md#assets-remote-useragent)). | string  | value of `assets.remote.useragent.default` |
| language       | Language code used to look for a localized version of the file first (e.g.: `image.fr.jpg` for `image.jpg`).       | string  | `null`                                           |

:::tip
Assets can be manipulated with dedicated filters: see [Images](1-images.md) and [CSS, JavaScript and processing](2-processing.md).

To output an asset as an HTML element (`<link>`, `<script>`, `<img>`, `<audio>`, `<video>`), use the [`html()`](../templates/reference/1-functions.md#html) function.
:::

:::info
You don't need to clear the [cache](../templates/6-cache.md) after modifying an asset: the cache is automatically cleared when the file is modified or when the file name is changed.
:::

_Examples:_

```twig
{# CSS #}
{{ asset('styles.css') }}
{# CSS bundle #}
{{ asset(['poole.css', 'hyde.css'], {filename: 'styles.css'}) }}
{# JavaScript #}
{{ asset('scripts.js') }}
{# image #}
{{ asset('image.jpeg') }}
{# localized image (looks for "image.fr.jpeg" first) #}
{{ asset('image.jpeg', {language: 'fr'}) }}
{# audio #}
{{ asset('audio.mp3') }}
{# video #}
{{ asset('video.mp4') }}
{# remote file #}
{{ asset('https://cdnjs.cloudflare.com/ajax/libs/anchor-js/4.3.1/anchor.min.js', {minify: false}) }}
{# with filter #}
{{ asset('styles.css')|minify }}
{{ asset('styles.scss')|to_css|minify }}
```

### Asset attributes

Assets created with the `asset()` function expose some useful attributes.

Common:

- `file`: filesystem path
- `missing`: `true` if the file is not found and `ignore_missing` is enabled
- `path`: public path
- `ext`: file extension
- `type`: media type (e.g.: `image`)
- `subtype`: media sub type (e.g.: `image/jpeg`)
- `size`: size in bytes
- `content`: file content
- `hash`: file content hash (xxHash 128)
- `dataurl`: data URL encoded in Base64
- `integrity`: integrity hash

Remote:

- `url`: URL of the remote file

Bundle:

- `files`: array of filesystem paths, in case of a bundle

Image:

- `width`: image width in pixels
- `height`: image height in pixels
- `exif`: image EXIF data as array

Audio:

- `duration`: duration in seconds.microseconds
- `getAudio.bitrate`: bitrate in bps
- `getAudio.channel`: `stereo`, `dual_mono`, `joint_stereo` or `mono`

Video:

- `duration`: duration in seconds
- `width`: width in pixels
- `height`: height in pixels

_Examples:_

```twig
{# image width in pixels #}
{{ asset('image.png').width }}px
{# photo's date as a Unix timestamp #}
{{ asset('photo.jpeg').exif.EXIF.DateTimeOriginal|date('U') }}
{# audio duration in seconds #}
{{ asset('song.mp3').duration|round }} s
{# audio bitrate #}
{{ asset('song.mp3').getAudio.bitrate }} bps
{# video duration in seconds #}
{{ asset('movie.mp4').duration|round }} s
{# file integrity hash #}
{% set integrity = asset('styles.scss').integrity %}
```

## integrity

Creates the hash (`sha384` by default) of a file (from an asset or a path).

```twig
{{ integrity(asset, algo) }}
```

Used for SRI ([Subresource Integrity](https://developer.mozilla.org/en-US/docs/Web/Security/Subresource_Integrity)).

_Examples:_

```twig
{{ integrity('styles.css') }}
{# sha384-oGDH3qCjzMm/vI+jF4U5kdQW0eAydL8ZqXjHaLLGduOsvhPRED9v3el/sbiLa/9g #}
{{ integrity('styles.css', 'sha512') }}
```

## Tests

Twig [tests](https://twig.symfony.com/doc/templates.html#test-operator) to check assets:

- `asset`: the variable is an asset
- `image_large`: the asset is a landscape image of at least 600×315 pixels
- `image_square`: the asset is an image with the same width and height

_Example:_

```twig
{% set image = asset(page.image) %}
{% if image is image_large %}
  <meta name="twitter:card" content="summary_large_image">
{% endif %}
```
