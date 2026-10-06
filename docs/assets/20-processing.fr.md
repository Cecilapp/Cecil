<!--
title: "CSS, JavaScript et traitements"
description: "Compilez Sass, minifiez, ajoutez une empreinte, intégrez ou embarquez les assets."
date: 2026-05-26
updated: 2026-10-05
path: documentation/assets/traitements
-->
# CSS, JavaScript et traitements

## fingerprint

Ajoutez l'empreinte digitale du contenu du fichier au nom du fichier.

```twig
{{ asset(path)|fingerprint }}
{{ path|fingerprint }}
```

_Exemples :_

```twig
{{ asset('styles.css')|fingerprint }}
```

## minify

Réduire un fichier CSS ou JavaScript.

```twig
{{ asset(path)|minify }}
```

_Exemples :_

```twig
{{ asset('styles.css')|minify }}
{{ asset('scripts.js')|minify }}
```

## minify_css

Réduire une chaîne CSS.

```twig
{{ variable|minify_css }}
```

```twig
{% apply minify_css %}
{# CSS here #}
{% endapply %}
```

_Exemples :_

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

Réduire une chaîne JavaScript.

```twig
{{ variable|minify_js }}
```

```twig
{% apply minify_js %}
{# JavaScript here #}
{% endapply %}
```

_Exemples :_

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

Compile une chaîne [Sass](https://sass-lang.com) en CSS.

```twig
{{ variable|scss_to_css }}
```

```twig
{% apply scss_to_css %}
{# SCSS here #}
{% endapply %}
```

Alias : `sass_to_css`.

_Exemples :_

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

Compile un fichier [Sass](https://sass-lang.com) en CSS.

```twig
{{ asset(path)|to_css }}
{{ path|to_css }}
```

_Exemples :_

```twig
{{ asset('styles.scss')|to_css }}
```

## inline

Affiche le contenu d'un _Asset_.

```twig
{{ asset(path)|inline }}
```

_Exemple:_

```twig
{{ asset('styles.css')|inline }}
```

## dataurl

Renvoie l'[URL de données](https://developer.mozilla.org/docs/Web/HTTP/Basics_of_HTTP/Data_URIs) d'un actif.

```twig
{{ asset(path)|dataurl }}
{{ asset(image_path)|dataurl }}
```
