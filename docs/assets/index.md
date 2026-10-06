<!--
title: "Assets"
description: "Handle assets (images, stylesheets, scripts, etc.) with the asset() function: process, optimize and fingerprint them."
date: 2021-05-07
updated: 2026-10-05
weight: 4
sortby: weight
-->
# Assets

## asset

An asset is a resource useable in templates, like CSS, JavaScript, image, audio, video, etc.

The `asset()` function creates an _asset_ object from a file path, an array of files path (bundle) or an URL (remote file), and are processed (minified, fingerprinted, etc.) according to the [configuration](../configuration/6-assets.md).

Resource files must be stored in the `assets/` (or `static/`)  directory.

```twig
{{ asset(path, {options}) }}
```

| Option         | Description                                                                              | Type    | Default                      |
| -------------- | ---------------------------------------------------------------------------------------- | ------- | ---------------------------- |
| filename       | Save bundle to a custom file name.                                                       | string  | `styles.css` or `scripts.js` |
| ignore_missing | Do not stop build if file is not found.                                                  | boolean | `false`                      |
| fingerprint    | Add content hash to the file name.                                                       | boolean | `true`                       |
| minify         | Compress CSS or JavaScript.                                                              | boolean | `true`                       |
| optimize       | Compress image.                                                                          | boolean | `false`                      |
| fallback       | Load a local asset if remote file is not found.                                          | string  | ``                           |
| useragent      | User agent key (See [Assets configuration](../configuration/6-assets.md#assets-remote-useragent)). | string  | `default`                    |

:::tip
You can use [filters](../templates/reference/3-filters.md) to manipulate assets.
:::

:::info
You don't need to clear the [cache](../templates/6-cache.md) after modifying an asset: the cache is automatically cleared when the file is modified or when the file name is changed.
:::

_Examples:_

```twig
{# CSS #}
{{ asset('styles.css') }}
{# CSS bundle #}
{{ asset(['poole.css', 'hyde.css'], {filename: styles.css}) }}
{# JavaScript #}
{{ asset('scripts.js') }}
{# image #}
{{ asset('image.jpeg') }}
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
- `missing`: `true` if file is not found but missing is allowed
- `path`: public path
- `ext`: file extension
- `type`: media type (e.g.: `image`)
- `subtype`: media sub type (e.g.: `image/jpeg`)
- `size`: size in octets
- `content`: file content
- `hash`: file content hash (md5)
- `dataurl`: data URL encoded in Base64
- `integrity`: integrity hash

Remote:

- `url`: URL of the remote file

Bundle:

- `files`: array of filesystem path in case of a bundle

Image:

- `width`: image width in pixels
- `height`: image height in pixels
- `exif`: image EXIF data as array

Audio:

- `duration`: duration in seconds.microseconds
- `bitrate`: bitrate in bps
- `channel`: 'stereo', 'dual_mono', 'joint_stereo' or 'mono'

Video:

- `duration`: duration in seconds
- `width`: width in pixels
- `height`: height in pixels

_Examples:_

```twig
{# image width in pixels #}
{{ asset('image.png').width }}px
{# photo's date in seconds #}
{{ asset('photo.jpeg').exif.EXIF.DateTimeOriginal|date('U') }}
{# audio duration in seconds #}
{{ asset('song.mp3').duration|round }} s
{# video duration in seconds #}
{{ asset('movie.mp4').duration|round }} s
{# file integrity hash #}
{% set integrity = asset('styles.scss').integrity %}
```

## integrity

Creates the hash (`sha384`) of a file (from an asset or a path).

```twig
{{ integrity(asset) }}
```

Used for SRI ([Subresource Integrity](https://developer.mozilla.org/fr/docs/Web/Security/Subresource_Integrity)).

_Example:_

```twig
{{ integrity('styles.css') }}
{# sha384-oGDH3qCjzMm/vI+jF4U5kdQW0eAydL8ZqXjHaLLGduOsvhPRED9v3el/sbiLa/9g #}
```
