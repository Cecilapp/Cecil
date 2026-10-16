<!--
title: "CSS, JavaScript et traitements"
description: "Compilez Sass, minifiez, ajoutez une empreinte, intégrez ou embarquez les assets."
date: 2026-05-26
updated: 2026-10-16
path: documentation/assets/traitements
-->
# CSS, JavaScript et traitements

Cecil fournit des filtres Twig pour compiler du Sass, minifier, ajouter une empreinte, intégrer ou embarquer (sous forme d’URL de données) les assets CSS et JavaScript.

## fingerprint

Ajoute une empreinte (hash) du contenu du fichier à son nom.

```twig
{{ asset(path)|fingerprint }}
{{ path|fingerprint }}
```

_Exemple :_

```twig
{{ asset('styles.css')|fingerprint }}
```

## minify

Minifie un fichier CSS ou JavaScript.

```twig
{{ asset(path)|minify }}
{{ path|minify }}
```

_Exemples :_

```twig
{{ asset('styles.css')|minify }}
{{ asset('scripts.js')|minify }}
```

## minify_css

Minifie une chaîne CSS.

```twig
{{ variable|minify_css }}
```

```twig
{% apply minify_css %}
{# CSS ici #}
{% endapply %}
```

_Exemples :_

```twig
{% set styles = 'du CSS ici' %}
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

Minifie une chaîne JavaScript.

```twig
{{ variable|minify_js }}
```

```twig
{% apply minify_js %}
{# JavaScript ici #}
{% endapply %}
```

_Exemples :_

```twig
{% set script = 'du JavaScript ici' %}
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
{# SCSS ici #}
{% endapply %}
```

Alias : `sass_to_css`.

_Exemples :_

```twig
{% set scss = 'du SCSS ici' %}
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

_Exemple :_

```twig
{{ asset('styles.scss')|to_css }}
```

## inline

Affiche le contenu d’un _asset_.

```twig
{{ asset(path)|inline }}
```

_Exemple :_

```twig
{{ asset('styles.css')|inline }}
```

## dataurl

Renvoie l’[URL de données](https://developer.mozilla.org/fr/docs/Web/HTTP/Basics_of_HTTP/Data_URIs) (data URL) d’un asset (ou d’un chemin).

Les images matricielles sont réencodées avec l’option [`assets.images.quality`](../configuration/6-assets.fr.md#assets-images) ; les autres fichiers (y compris SVG) sont encodés tels quels en Base64.

```twig
{{ asset(path)|dataurl }}
{{ path|dataurl }}
```

_Exemple :_

```twig
<img src="{{ asset('icon.png')|dataurl }}" alt="">
```
