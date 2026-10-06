<!--
title: "Fonctions"
description: "url, html, readtime, hash, cache_key, getenv, dump, etc."
date: 2026-05-26
updated: 2026-10-05
path: documentation/templates/reference/fonctions
-->
# Fonctions

> [Fonctions](https://twig.symfony.com/doc/functions/index.html) peut être appelée pour générer du contenu. Les fonctions sont appelées par leur nom suivi de parenthèses (`()`) et peuvent avoir des arguments.

## url

Crée une URL valide pour une page, une entrée de menu, un actif, un ID de page ou un chemin.

```twig
{{ url(value, {options}) }}
```

| Options   | Descriptif                                                                                                                                  | Tapez   | Par défaut |
| --------- | ------------------------------------------------------------------------------------------------------------------------------------------- | ------- | ---------- |
| canonique | Préfixez l'URL avec [`baseurl`](../../configuration/22-site.fr.md#baseurl) ou utilisez [`canonical.url`](../../configuration/22-site.fr.md#options-metatags) s'il existe. | booléen | `false`    |
| formats   | Définit la page [format de sortie](../../configuration/29-output.fr.md#output-formats) (par exemple : `json`).                                               | chaîne  | `html`     |
| langue    | Définit la page [langue](../../configuration/23-languages.fr.md#language) (ex. : `fr`).                                                                         | chaîne  | nul        |

_Exemples :_

```twig
{# page #}
{{ url(page) }}
{{ url(page, {canonical: true}) }}
{{ url(page, {format: json}) }}
{{ url(page, {language: fr}) }}
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
Pour plus de commodité, la fonction `url` est également disponible sous forme de filtre :

```twig
{# page #}
{{ page|url }}
{{ page|url({canonical: true, format: json, language: fr}) }}
{# asset #}
{{ asset('styles.css')|url }}
```

:::

:::tip
Lorsque la valeur est une chaîne, `url()` la « slugifie » pour trouver un ID de page correspondant (ex. : `url('tags/My Tag')` retourne l’URL de la page `tags/my-tag`). Si aucune page ne correspond, la chaîne est conservée comme chemin, avec les caractères invalides (ex. : espaces) encodés.
:::

## html

Crée un élément HTML à partir d'un actif (ou d'un tableau d'actifs avec des attributs personnalisés).

```twig
{{ html(asset, {attributes}, {options}) }}
{# dedicated functions for each common type of asset #}
{{ css(asset) }}
{{ js(asset) }}
{{ image(asset) }}
{{ audio(asset) }}
{{ video(asset) }}
```

| Options   | Descriptif                                                                                                                                                                                                                     | Tapez   |
| --------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | ------- |
| attributs | Ajoute le couple `name="value"` à l'élément HTML.                                                                                                                                                                              | tableau |
| options   | `{preload: boolean}` : préchargements.<br>Pour les images :<br>`{formats: array}` : ajoute des formats alternatifs.<br>`{responsive: bool|string}` : ajoute des images réactives (basées sur `width` ou des pixels `density`).<br>`{placeholder: string}` : remplit l'arrière-plan de l'image avant son chargement (`color` ou `lqip`). | tableau |

:::warning
Depuis la version ++8.42.0++, la fonction `html` remplace le filtre `html` obsolète.
:::

:::tip
Vous pouvez définir un comportement global par défaut des options d'images (`formats`, `responsive` et `placeholder`) via la [configuration des layouts](../../configuration/28-layouts.fr.md#layouts-images).

Lorsque [`layouts.images.dark_suffix`](../../configuration/28-layouts.fr.md#layouts-images) est configuré (par exemple `.dark`), Cecil recherche automatiquement une variante sombre de chaque image (par exemple `photo.dark.jpg` aux côtés de `photo.jpg`) et génère un élément `<picture>` avec un `<source media="(prefers-color-scheme: dark)">`.

De la même manière, lorsque [`layouts.images.mobile_suffix`](../../configuration/28-layouts.fr.md#layouts-images) est configuré (par exemple `.mobile`), Cecil recherche une variante mobile de chaque image (par exemple `photo.mobile.jpg`) et ajoute un `<source>` avec la media query [`layouts.images.mobile_media_query`](../../configuration/28-layouts.fr.md#layouts-images). Si une variante sombre de l’image mobile existe (par exemple `photo.mobile.dark.jpg`), elle est utilisée sur mobile en mode sombre.
:::

_Exemples :_

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
Pour plus de commodité, la fonction `html` reste disponible en tant que filtre (mais est considérée comme obsolète) :

```twig
{{ asset|html({attributes}, {options}) }}
```

:::

## readtime

Détermine le temps de lecture d'un texte, en minutes.

```twig
{{ readtime(value) }}
```

_Exemple:_

```twig
{{ readtime(page.content) }} min
```

## hash

Calcule le hachage d'un objet, d'un tableau ou d'une chaîne avec un algorithme donné.

```twig
{{ hash(value, algorithm) }}
```

`algorithm` peut être n'importe quel algorithme pris en charge par la fonction `hash()` de PHP (par exemple : `md5`, `sha256`, etc.). La valeur par défaut est `xxh128`.

_Exemple:_

```twig
{{ hash('my string', 'sha256') }}
```

## cache_key

Calcule une clé de cache pour [_fragments_ cache](../17-cache.fr.md#fragments-de-cache) en fonction d'un nom et d'une valeur facultative.

```twig
{% cache cache_key(name, value) %}
  {# cacheable content #}
{% endcache %}
```

La fonction ajoute un hachage de la valeur (peut être une chaîne, un tableau ou un objet) au nom (ainsi que la langue actuelle et l'ID de build pour être sûr que la clé de cache générée est unique), donc si la valeur est modifiée, la clé de cache est également modifiée et le cache est automatiquement vidé.

## getenv

Obtient la valeur d'une variable d'environnement à partir de sa clé.

```twig
{{ getenv(var) }}
```

_Exemple:_

```twig
{{ getenv('VAR') }}
```

## dump

La fonction `dump` affiche les informations sur une variable de modèle. Ceci est surtout utile pour déboguer un modèle qui ne se comporte pas comme prévu en introspectant ses variables :

```twig
{{ dump(user) }}
```

:::important
Le [_debug mode_](../../configuration/22-site.fr.md#debug) doit être activé.
:::

## d

La fonction `d()` est la version HTML de [`dump()`](#dump) et utilise le [Symfony VarDumper Component](https://symfony.com/doc/5.4/components/var_dumper.html) en arrière-plan.

```twig
{{ d(variable, {theme: light}) }}
```

- Si _variable_ n'est pas fourni, la fonction renvoie le contexte Twig actuel
- Les thèmes disponibles sont « clair » (par défaut) et « sombre »

:::important
Le [_debug mode_](../../configuration/22-site.fr.md#debug) doit être activé.
:::
