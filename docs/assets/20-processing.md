<!--
title: "CSS, JavaScript and processing"
description: "Compile Sass, minify, fingerprint, inline or embed assets."
date: 2021-05-07
updated: 2026-10-05
-->
# CSS, JavaScript and processing

## fingerprint

Add the file content finger print to the file name.

```twig
{{ asset(path)|fingerprint }}
{{ path|fingerprint }}
```

_Examples:_

```twig
{{ asset('styles.css')|fingerprint }}
```

## minify

Minifying a CSS or a JavaScript file.

```twig
{{ asset(path)|minify }}
```

_Examples:_

```twig
{{ asset('styles.css')|minify }}
{{ asset('scripts.js')|minify }}
```

## minify_css

Minifying a CSS string.

```twig
{{ variable|minify_css }}
```

```twig
{% apply minify_css %}
{# CSS here #}
{% endapply %}
```

_Examples:_

```twig
{% set styles = 'some CSS here' %}
{{ styles|minify_css }}
```

```twig
<style>
{% apply minify_css %}
  html {
    background-color: #fcfcfc;
    color: #444;
  }
{% endapply %}
</style>
```

## minify_js

Minifying a JavaScript string.

```twig
{{ variable|minify_js }}
```

```twig
{% apply minify_js %}
{# JavaScript here #}
{% endapply %}
```

_Examples:_

```twig
{% set script = 'some JavaScript here' %}
{{ script|minify_js }}
```

```twig
<script>
{% apply minify_js %}
  var test = 'test';
  console.log(test);
{% endapply %}
</script>
```

## scss_to_css

Compiles a [Sass](https://sass-lang.com) string to CSS.

```twig
{{ variable|scss_to_css }}
```

```twig
{% apply scss_to_css %}
{# SCSS here #}
{% endapply %}
```

Alias: `sass_to_css`.

_Examples:_

```twig
{% set scss = 'some SCSS here' %}
{{ scss|scss_to_css }}
```

```twig
<style>
{% apply scss_to_css %}
  $color: #fcfcfc;
  div {
    color: lighten($color, 20%);
  }
{% endapply %}
</style>
```

## to_css

Compiles a [Sass](https://sass-lang.com) file to CSS.

```twig
{{ asset(path)|to_css }}
{{ path|to_css }}
```

_Examples:_

```twig
{{ asset('styles.scss')|to_css }}
```

## inline

Outputs the content of an _Asset_.

```twig
{{ asset(path)|inline }}
```

_Example:_

```twig
{{ asset('styles.css')|inline }}
```

## dataurl

Returns the [data URL](https://developer.mozilla.org/docs/Web/HTTP/Basics_of_HTTP/Data_URIs) of an asset.

```twig
{{ asset(path)|dataurl }}
{{ asset(image_path)|dataurl }}
```
