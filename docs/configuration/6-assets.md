<!--
title: "Assets"
description: "Assets directory, compilation, minification, images and CDN."
date: 2021-05-07
updated: 2026-10-16
-->
# Assets

Assets management (images, CSS and JS files).

## assets.dir

Assets source directory (`assets` by default).

```yaml
assets:
  dir: assets
```

## assets.target

Directory where remote and resized assets files are saved (root by default).

```yaml
assets:
  target: ''
```

## assets.fingerprint

Enables fingerprinting (cache busting) for assets files (`true` by default).

```yaml
assets:
  fingerprint: true
```

## assets.compile

[Sass](https://sass-lang.com) files compilation options. See the [documentation of scssphp](https://scssphp.github.io/scssphp/docs/#output-formatting) for options details.

```yaml
assets:
  compile:
    style: expanded      # compilation style (`expanded` or `compressed`. `expanded` by default)
    import: [sass, scss] # list of imported paths (`[sass, scss, node_modules]` by default)
    sourcemap: false     # enables sourcemap in debug mode (`false` by default)
    variables: {}        # preset variables, as `name: value` pairs (empty by default)
```

:::info
`sourcemap` is used to debug SCSS compilation ([debug mode](1-site.md#debug) must be enabled).
:::

## assets.minify

Enables CSS and JS minification (`true` by default).

```yaml
assets:
  minify: true
```

## assets.images

Images management.

```yaml
assets:
  images:
    optimize: false # enables images optimization with JpegOptim, Optipng, Pngquant 2, SVGO 1, Gifsicle, cwebp, avifenc (`false` by default)
    quality: 75     # image quality used by optimization and resizing (`75` by default)
    responsive:
      widths: [480, 640, 768, 1024, 1366, 1600, 1920] # `srcset` attribute images widths
      sizes:
        default: '100vw' # default `sizes` attribute (`100vw` by default)
      densities: [1, 2, 3] # `srcset` attribute pixel densities
```

## assets.images.cdn

URL of image assets can be easily replaced by a provided CDN `url`.

```yaml
assets:
  images:
    cdn:
      enabled: false  # enables Image CDN (`false` by default)
      svg: false      # includes SVG images (`false` by default)
      canonical: true # `image_url` is canonical (instead of a relative path) (`true` by default)
      remote: true    # also handles remote images (`true` by default)
      account: 'xxxx' # provider account
      url: 'https://provider.tld/%account%/%image_url%?w=%width%&q=%quality%&format=%format%'
```

`url` is a pattern that contains variables:

- `%account%` replaced by the `assets.images.cdn.account` option
- `%image_url%` replaced by the image canonical URL or `path`
- `%width%` replaced by the image width
- `%quality%` replaced by the `assets.images.quality` option
- `%format%` replaced by the image format

See [**CDN providers**](../assets/3-cdn-providers.md).

## assets.remote.useragent

User agent used to download remote assets.

```yaml
assets:
  remote:
    useragent:
      default: <string> # user agent of HTTP requests
```

:::info
For a given asset, the user agent can be replaced with the `useragent` option of the [`asset()`](../assets/index.md#asset) function.
:::

## assets.remote.timeout

Timeout of remote assets downloads, in seconds (`30` by default).

```yaml
assets:
  remote:
    timeout: 30
```

---
