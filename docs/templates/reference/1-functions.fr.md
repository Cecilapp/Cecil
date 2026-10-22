<!--
title: "Fonctions"
description: "url, html, readtime, hash, cache_key, getenv, dump, tests, etc."
date: 2026-05-26
updated: 2026-10-22
path: documentation/templates/reference/fonctions
-->
# Fonctions

> Les [fonctions](https://twig.symfony.com/doc/functions/index.html) peuvent être appelées pour générer du contenu. Les fonctions sont appelées par leur nom suivi de parenthèses (`()`) et peuvent avoir des arguments.

:::info
Voir aussi : les fonctions dédiées aux assets (`asset`, `integrity`, `image_srcset`, `image_sizes`, `image_from_website`, etc.) sont documentées dans la section [Assets](../../assets/index.fr.md).
:::

## url

Crée une URL valide pour une page, une entrée de menu, un asset, un ID de page ou un chemin.

```twig
{{ url(value, {options}) }}
```

| Option    | Description                                                                                                                                 | Type    | Par défaut |
| --------- | ------------------------------------------------------------------------------------------------------------------------------------------- | ------- | ---------- |
| canonical | Préfixe l’URL avec [`baseurl`](../../configuration/1-site.fr.md#baseurl) ou utilise [`canonical.url`](../../configuration/1-site.fr.md#options-metatags) s’il existe. | booléen | valeur de [`canonicalurl`](../../configuration/1-site.fr.md#canonicalurl) (`false` par défaut) |
| format    | Définit le [format de sortie](../../configuration/8-output.fr.md#output-formats) de la page (ex. : `json`).                                                  | chaîne  | `html`     |
| language  | Définit la [langue](../../configuration/2-languages.fr.md#language) de la page (ex. : `fr`).                                                                 | chaîne  | langue actuelle |

_Exemples :_

```twig
{# page #}
{{ url(page) }}
{{ url(page, {canonical: true}) }}
{{ url(page, {format: 'json'}) }}
{{ url(page, {language: 'fr'}) }}
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
Pour plus de commodité, la fonction `url` est également disponible sous forme de filtre :

```twig
{# page #}
{{ page|url }}
{{ page|url({canonical: true, format: 'json', language: 'fr'}) }}
{# asset #}
{{ asset('styles.css')|url }}
```

:::

:::tip
Lorsque la valeur est une chaîne, `url()` la « slugifie » pour trouver un ID de page correspondant (ex. : `url('tags/My Tag')` retourne l’URL de la page `tags/my-tag`). Si aucune page ne correspond, la chaîne est conservée comme chemin, avec les caractères invalides (ex. : espaces) encodés.
:::

## html

Crée un élément HTML à partir d’un asset (ou d’un tableau d’assets avec des attributs personnalisés).

```twig
{{ html(asset, {attributes}, {options}) }}
{# dedicated functions for each common type of asset #}
{{ css(asset) }}
{{ js(asset) }}
{{ image(asset) }}
{{ audio(asset) }}
{{ video(asset) }}
```

| Argument   | Description                                                                                                                                                                                                                    | Type    |
| ---------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | ------- |
| attributes | Ajoute le couple `name="value"` à l’élément HTML.                                                                                                                                                                              | tableau |
| options    | `{preload: boolean}` : précharge.<br>Pour les images :<br>`{formats: array}` : ajoute des formats alternatifs.<br>`{responsive: bool|string}` : ajoute des images responsives (basées sur la largeur, `width`, ou la densité de pixels, `density`).<br>`{placeholder: string}` : remplit l’arrière-plan de l’image avant son chargement (`color` ou `lqip`). | tableau |

:::warning
Depuis la version ++8.42.0++, la fonction `html` remplace le filtre `html` obsolète.
:::

:::tip
Vous pouvez définir un comportement global par défaut des options d’images (`formats`, `responsive` et `placeholder`) via la [configuration des layouts](../../configuration/7-layouts.fr.md#layouts-images).

Lorsque [`layouts.images.dark_suffix`](../../configuration/7-layouts.fr.md#layouts-images) est configuré (par exemple `.dark`), Cecil recherche automatiquement une variante sombre de chaque image (par exemple `photo.dark.jpg` aux côtés de `photo.jpg`) et génère un élément `<picture>` avec un `<source media="(prefers-color-scheme: dark)">`.

De la même manière, lorsque [`layouts.images.mobile_suffix`](../../configuration/7-layouts.fr.md#layouts-images) est configuré (par exemple `.mobile`), Cecil recherche une variante mobile de chaque image (par exemple `photo.mobile.jpg`) et ajoute un `<source>` avec la media query [`layouts.images.mobile_media_query`](../../configuration/7-layouts.fr.md#layouts-images). Si une variante sombre de l’image mobile existe (par exemple `photo.mobile.dark.jpg`), elle est utilisée sur mobile en mode sombre.
:::

_Exemples :_

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
Pour plus de commodité, la fonction `html` reste disponible en tant que filtre (obsolète) :

```twig
{{ asset|html({attributes}, {options}) }}
```

:::

## readtime

Détermine le temps de lecture d’un texte, en minutes (sur la base de 200 mots par minute, 1 minute minimum).

```twig
{{ readtime(value) }}
```

_Exemple :_

```twig
{{ readtime(page.content) }} min
```

## hash

Calcule le hachage d’un objet, d’un tableau ou d’une chaîne avec un algorithme donné.

```twig
{{ hash(value, algorithm) }}
```

`algorithm` peut être n’importe quel algorithme pris en charge par la fonction `hash()` de PHP (ex. : `md5`, `sha256`, etc.). La valeur par défaut est `xxh128`.

_Exemple :_

```twig
{{ hash('my string', 'sha256') }}
```

## cache_key

Calcule une clé pour le [cache de _fragments_](../6-cache.fr.md#cache-de-fragments) en fonction d’un nom et d’une valeur facultative.

```twig
{% cache cache_key(name, value) %}
  {# cacheable content #}
{% endcache %}
```

La fonction ajoute au nom un hachage de la valeur (qui peut être une chaîne, un tableau ou un objet), ainsi que la langue actuelle et l’ID de génération, pour garantir que la clé de cache générée est unique : si la valeur change, la clé de cache change aussi et une nouvelle entrée de cache est générée.

## getenv

Obtient la valeur d’une variable d’environnement à partir de sa clé.

```twig
{{ getenv(var) }}
```

_Exemple :_

```twig
{{ getenv('VAR') }}
```

## dump

La fonction `dump` affiche les informations sur une variable de template. C’est surtout utile pour déboguer un template qui ne se comporte pas comme prévu, en inspectant ses variables :

```twig
{{ dump(user) }}
```

:::important
Le [_mode debug_](../../configuration/1-site.fr.md#debug) doit être activé.
:::

## d

La fonction `d()` est la version HTML de [`dump()`](#dump) et utilise le [composant Symfony VarDumper](https://symfony.com/doc/current/components/var_dumper.html) en arrière-plan.

```twig
{{ d(variable, {theme: 'dark'}) }}
```

- Si _variable_ n’est pas fournie, la fonction affiche le contexte Twig actuel
- Les thèmes disponibles sont `light` (par défaut) et `dark`

:::important
Le [_mode debug_](../../configuration/1-site.fr.md#debug) doit être activé.
:::

## Tests

Les [tests](https://twig.symfony.com/doc/tests/index.html) s’utilisent avec l’opérateur `is`.

| Test           | Description                                                                                       |
| -------------- | ------------------------------------------------------------------------------------------------- |
| `asset`        | La variable est un [asset](../../assets/index.fr.md#asset).                                       |
| `image_large`  | L’asset est une image au format paysage d’au moins 600 × 315 pixels (ex. : une image de couverture). |
| `image_square` | L’asset est une image carrée (largeur et hauteur identiques).                                      |
| `enabled`      | L’option est activée : `true`, un tableau associatif sans clé `enabled` ou avec `enabled: true`, ou toute autre valeur sauf `false` et `null`. |
| `external`     | L’URL (ou l’URL d’une entrée de menu) est externe : elle a un schéma (`https:`, `mailto:`, etc.) ou commence par `//`, et ne commence pas par `baseurl`. |

_Exemple :_

```twig
{% set image = asset(page.image) %}
{% if image is image_large %}
  {{ html(image, {alt: page.title}) }}
{% endif %}
```

```twig
{% if config.cache is enabled %}
  {# `cache: false` et `cache: {enabled: false}` sont tous deux désactivés #}
{% endif %}
```

```twig
{% for item in site.menus.main|sort_by_weight %}
  <a href="{{ url(item.url) }}"{% if item is external %} rel="noopener" target="_blank"{% endif %}>{{ item.name }}</a>
{% endfor %}
```

:::info
Le test `enabled` ne vérifie que la valeur donnée, pas ses parents : `config.optimize.html is enabled` peut être vrai alors que `optimize` est désactivé.
Une option personnalisée peut ne pas exister : utilisez le filtre `default` (ex. `config.myoption|default(false) is enabled`).
:::

## Obsolètes

Les fonctions suivantes sont obsolètes (un avertissement de dépréciation est émis lorsqu’elles sont utilisées) :

| Fonction           | Remplacement                                                                     |
| ------------------ | -------------------------------------------------------------------------------- |
| `minify()`         | filtre [`minify`](../../assets/2-processing.fr.md#minify)                        |
| `toCSS()`          | filtre [`to_css`](../../assets/2-processing.fr.md#to-css)                        |
| `image_from_url()` | fonction [`image_from_website()`](../../assets/1-images.fr.md#image-from-website) |
