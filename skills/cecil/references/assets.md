# Assets and Build Optimization

Assets (CSS, Sass, JavaScript, images, audio, video) are handled in templates with `asset()`, and printed with `html()` or `url()`. Images in Markdown content are described in [content.md](content.md#images).

## Contents

- [The asset() Function](#the-asset-function)
- [Asset Attributes](#asset-attributes)
- [CSS, JavaScript and Sass](#css-javascript-and-sass)
- [Images](#images)
- [Image Variants (Dark and Mobile)](#image-variants-dark-and-mobile)
- [Image CDN](#image-cdn)
- [Asset Processing Options](#asset-processing-options)
- [Code Syntax Highlighting](#code-syntax-highlighting)
- [Performance Tips](#performance-tips)

## The asset() Function

```twig
{{ asset('css/styles.css') }}                                 {# prints the URL and publishes the file #}
{{ asset(['poole.css', 'hyde.css'], {filename: 'styles.css'}) }} {# bundle #}
{{ asset('https://cdn.tld/lib.js', {minify: false, fallback: 'js/lib.js'}) }} {# remote file, local fallback #}
{{ asset('banner.jpg', {language: site.language}) }}         {# looks for banner.fr.jpg first #}
{{ asset('optional.css', {ignore_missing: true}) }}          {# doesn't fail; check `.missing` #}
```

- Files are looked up in `assets/`, then `static/`, then the themes.
- Options: `filename` (bundles: `styles.css` / `scripts.js` by default), `ignore_missing`, `fingerprint` (`assets.fingerprint`), `minify` (`assets.minify`), `optimize` (`assets.images.optimize`), `fallback`, `useragent`, `language`.
- A file in `assets/` is published only when an asset of it is printed (or used by a filter/function); printing the asset outputs its URL.
- An asset is rebuilt when its file changes: no need to clear the cache.

## Asset Attributes

`path`, `file`, `ext`, `type` (`image`), `subtype` (`image/jpeg`), `size`, `content`, `hash`, `dataurl`, `integrity`, `missing`; `url` for a remote file, `files` for a bundle; `width`, `height` and `exif` for an image; `duration` (and `width`, `height` for a video) for audio and video.

```twig
{% set img = asset(page.image) %}
<img src="{{ url(img) }}" width="{{ img.width }}" height="{{ img.height }}" alt="{{ page.title|e('html_attr') }}">
<script src="{{ url(asset('js/app.js')) }}" integrity="{{ integrity(asset('js/app.js')) }}" crossorigin="anonymous"></script> {# sha384 by default #}
{{ asset('icons/logo.svg').content }}                       {# inline SVG #}
```

## CSS, JavaScript and Sass

```twig
{{ html(asset('css/styles.scss')|to_css) }}                {# Sass file compiled to CSS #}
{{ html(asset('js/app.js'), {defer: true}) }}
<style>{% apply scss_to_css %}$c: #333; body { color: $c; }{% endapply %}</style> {# alias: sass_to_css #}
<style>{% apply minify_css %}…{% endapply %}</style>
<script>{% apply minify_js %}…{% endapply %}</script>
<style>{{ asset('css/critical.css')|inline }}</style>
```

- `minify`, `to_css`, `fingerprint` and `dataurl` work on an asset (or a path); `minify_css`, `minify_js` and `scss_to_css` on a string.
- Sass options:

  ```yaml
  assets:
    compile:
      style: compressed            # or expanded (default)
      import: [sass, scss, node_modules] # import paths (default), resolved in assets/ and static/ (site and themes)
      sourcemap: false             # in debug mode only
      variables: {primary: '#0d6efd'} # preset Sass variables (e.g. from theme options)
  ```

## Images

```twig
{% set img = asset(page.image) %}
{{ html(img, {alt: page.title}, {responsive: true, formats: ['avif', 'webp'], placeholder: 'color'}) }}
{{ img|resize(300) }}                    {# width only: keeps the aspect ratio #}
{{ img|resize(height: 200) }}            {# height only #}
{{ img|resize(1200, 630) }}              {# both: fits, then crops and centers (social image) #}
{{ img|resize(width: 1200, height: 630, remove_animation: true) }}
{{ img|webp(80) }} {{ img|avif }}        {# quality: assets.images.quality by default #}
{{ img|lqip }}                           {# 100 px blurred placeholder, as data URL #}
{{ img|dominant_color }}                 {# rgb(120 85 60) #}
{{ asset('icon.png')|resize(512)|maskable }} {# PWA maskable icon (padding 20 % by default) #}
<img src="{{ url(img) }}" srcset="{{ image_srcset(img) }}" sizes="{{ image_sizes('hero') }}" width="{{ img.width }}" height="{{ img.height }}" alt="">
{{ image_from_website('https://example.com/', {alt: ''}, {fallback: 'images/default.png'}) }} {# og:image of a website, or null #}
```

- Resized images are saved in `/thumbnails/<width>x<height>/<path>`; the original is not altered. ICO files are supported.
- The image driver is Imagick if available, then libvips (`intervention/image-driver-vips` + the `ffi` extension, not with `cecil.phar`), then GD. WebP and AVIF support depends on the driver: `html()` skips unsupported formats.
- `{{ img|webp }}` alone is a URL: inside `<picture>`, use it in a `<source type="image/webp" srcset="…">`, or let `html()` with `formats` build the `<picture>`.
- `image_large` / `image_square` tests check the image shape (e.g. `{% if img is image_large %}` for a cover image).

Responsive and quality settings, used by `html()` (`responsive`), `image_srcset()` and the Markdown images:

```yaml
assets:
  images:
    quality: 75
    responsive:
      widths: [480, 640, 768, 1024, 1366, 1600, 1920]
      sizes:
        default: '100vw'
        hero: '(min-width: 1200px) 1200px, 100vw' # `image_sizes('hero')` or `.hero` class in Markdown
      densities: [1, 2, 3]
layouts:
  images:              # default options of the `html()` function
    formats: [webp]
    responsive: width  # or density
    placeholder: color # or lqip
```

## Image Variants (Dark and Mobile)

Cecil can serve alternative versions of an image in a `<picture>` element, when files with a configured suffix exist next to the original image (e.g. `photo.dark.jpg`, `photo.mobile.jpg`, `photo.mobile.dark.jpg`):

```yaml
layouts:
  images:                          # images rendered with the `html` Twig function
    dark_suffix: .dark             # adds <source media="(prefers-color-scheme: dark)">
    mobile_suffix: .mobile         # adds <source> with the mobile media query
    mobile_media_query: "(max-width: 767px)"
pages:
  body:
    images:                        # same options for images in Markdown content
      dark_suffix: .dark
      mobile_suffix: .mobile
```

Both are disabled by default (`null`).

## Image CDN

Image URLs can be rewritten to an image CDN (ready-made settings for Cloudinary, Cloudimage, TwicPics, imgix and Netlify Image CDN in the [CDN providers documentation](https://cecil.app/documentation/assets/cdn-providers/)):

```yaml
assets:
  images:
    cdn:
      enabled: true
      canonical: true   # `%image_url%` is the canonical URL (`false`: the path)
      remote: true      # also remote images
      svg: false        # also SVG images
      account: xxxx
      url: 'https://provider.tld/%account%/%image_url%?w=%width%&q=%quality%&format=%format%'
```

## Asset Processing Options

```yaml
assets:
  minify: true       # default
  fingerprint: true  # default (cache busting)
  images:
    optimize: false  # default: optimize images (needs the binaries listed in configuration.md)
  remote:
    timeout: 30
    useragent:
      default: "Mozilla/5.0 …"
```

## Code Syntax Highlighting

Fenced code blocks are highlighted at build time (`pages.body.highlight: true` by default, `false` to disable). The engine can be changed:

```yaml
pages:
  body:
    highlight:
      engine: tempest  # `highlightjs` (default, `hljs-*` CSS classes) or `tempest` (fewer languages, `hl-*` CSS classes)
```

The theme CSS must match the engine's classes (the built-in `partials/highlight.css.twig` handles both).

In templates, the `highlight` filter does not use this option: its engine is set by `layouts.highlight.engine` (`highlightjs` by default) or by its `engine` argument: `{{ code|highlight('php', 'tempest') }}`. An unsupported language returns the escaped code (and logs a warning).

## Performance Tips

1. Use `draft: true` to exclude non-published content from builds
2. Enable output optimization in production (`optimize: true` or `build --optimize`); asset minification and fingerprinting are already enabled by default
3. Use output and format settings adapted to your pages types
4. Use responsive image options and image optimization when needed
5. Profile with `php cecil.phar build --metrics`, cache expensive template parts with the [fragments cache](templates.md#fragments-cache), and keep the `.cache/` directory between CI builds
