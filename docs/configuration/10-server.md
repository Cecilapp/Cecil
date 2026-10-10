<!--
title: "Server and optimization"
description: "Local server headers and output optimization."
date: 2021-05-07
updated: 2026-10-10
-->
# Server and optimization

These options configure the local preview server and the optimization of the generated files.

## Server

### server.headers

You can define custom [HTTP headers](https://developer.mozilla.org/docs/Glossary/Response_header), used by the local preview server.

:::warning
Since version ++8.38.0++, the `headers` option has been moved to the `server.headers` section.
:::

```yaml
server:
  headers:
    - path: <path> # Relative path, prefixed with a slash. Support "*" wildcard.
      headers:
        - key: <key>
          value: "<value>"
```

:::tip
It's useful to test custom [Content Security Policy](https://developer.mozilla.org/docs/Web/HTTP/CSP) or [Cache-Control](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cache-Control).
:::

_Example:_

```yaml
server:
  headers:
    - path: /*
      headers:
        - key: X-Frame-Options
          value: "SAMEORIGIN"
        - key: X-XSS-Protection
          value: "1; mode=block"
        - key: X-Content-Type-Options
          value: "nosniff"
        - key: Content-Security-Policy
          value: "default-src 'self'; object-src 'self'; img-src 'self'"
        - key: Strict-Transport-Security
          value: "max-age=31536000; includeSubDomains; preload"
    - path: /assets/*
      headers:
        - key: Cache-Control
          value: "public, max-age=31536000"
    - path: /foo.html
      headers:
        - key: Foo
          value: "bar"
```

---

## Optimize

The optimization options allow to enable compression of output files: HTML, CSS, JavaScript and image.

```yaml
optimize:
  enabled: false     # enables files optimization (`false` by default)
  html:
    enabled: true    # enables HTML files optimization
    ext: [html, htm]   # supported files extensions
  css:
    enabled: true    # enables CSS files optimization
    ext: [css]         # supported files extensions
  js:
    enabled: true    # enables JavaScript files optimization
    ext: [js]          # supported files extensions
  images:
    enabled: true    # enables images files optimization
    ext: [jpeg, jpg, png, gif, webp, svg, avif] # supported files extensions
```

This option is disabled by default and can be enabled via:

```yaml
optimize: true
```

Once the global option is enabled, the 4 file types will be processed.  
It is possible to disable each of them via `enabled: false` and modify processed files extension via `ext`.

:::tip
It is also possible to enable this option through CLI when using the "build" and "serve" commands via the `--optimize` option.
:::

:::important
**Images** compressor will use these binaries if they are present in the system: [JpegOptim](https://github.com/tjko/jpegoptim), [Optipng](http://optipng.sourceforge.net/), [Pngquant 2](https://pngquant.org/), [SVGO](https://github.com/svg/svgo), [Gifsicle](http://www.lcdf.org/gifsicle/), [cwebp](https://developers.google.com/speed/webp/docs/cwebp) and [avifenc](https://github.com/AOMediaCodec/libavif).
:::

---
