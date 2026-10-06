<!--
title: "Images"
description: "Redimensionnez, recadrez et convertissez les images, générez des images responsives et des placeholders."
date: 2026-05-26
updated: 2026-10-05
-->
# Images

## image_srcset

Construit l'attribut HTML img `srcset` (responsive) d'un élément d'image.

```twig
{{ image_srcset(asset) }}
```

_Exemples :_

```twig
{% set asset = asset(image_path) %}
<img src="{{ url(asset) }}" width="{{ asset.width }}" height="{{ asset.height }}" alt="" class="asset" srcset="{{ image_srcset(asset) }}" sizes="{{ image_sizes('asset') }}">
```

## image_sizes

Renvoie l'attribut HTML img `sizes` basé sur un nom de classe CSS.
Il doit être utilisé conjointement avec la fonction [`image_srcset`](19-images.fr.md#image-srcset).

```twig
{{ image_sizes('class') }}
```

_Exemples :_

```twig
{% set asset = asset(image_path) %}
<img src="{{ url(asset) }}" width="{{ asset.width }}" height="{{ asset.height }}" alt="" class="asset" srcset="{{ image_srcset(asset) }}" sizes="{{ image_sizes('asset') }}">
```

## image_from_website

Construit l'élément HTML img à partir d'une URL de site Web en extrayant son image d'illustration.
Retourne `null` si aucune image n'est trouvée.

```twig
{{ image_from_website('url', {attributs}, {options}) }}
```

L'image est recherchée dans le HTML de la page avec les fallbacks suivants, le premier candidat pouvant être téléchargé en tant qu'image est utilisé :

1. Open Graph : `og:image:secure_url`, `og:image`, `og:image:url`
2. Twitter : `twitter:image`, `twitter:image:src`
3. `<link rel="image_src">`
4. Microdata : `itemprop="image"`
5. JSON-LD : propriété `image`
6. Première `<img>` de `<article>`, `<main>` ou `<body>`
7. `<link rel="apple-touch-icon">`
8. `<link rel="icon">`

Les URL relatives sont résolues par rapport à `<base href>` ou à l'URL de la page.

L'URL de l'image trouvée et l'image téléchargée sont mises en cache (voir [`cache.assets.remote.ttl`](../configuration/30-cache.fr.md)).

Options :

- `fallback` : chemin (ou URL) de l'image utilisée si aucune image n'est trouvée
- autres options de [`image`](../templates/reference/12-functions.fr.md#html) (ex. : `responsive`, `formats`)

_Exemples :_

```twig
{{ image_from_website('https://example.com/page-with-image.html') }}

{# avec une image de fallback #}
{{ image_from_website('https://example.com/', {alt: 'Illustration'}, {fallback: 'images/default.png'}) }}
```

## resize

Redimensionne une image à une largeur (en pixels) ou/et une hauteur (en pixels) spécifiée.

- Si seule la largeur est spécifiée, la hauteur est calculée pour conserver le rapport hauteur/largeur
- Si seule la hauteur est spécifiée, la largeur est calculée pour conserver le rapport hauteur/largeur
- Si la largeur et la hauteur sont spécifiées, l'image est redimensionnée pour s'adapter aux dimensions données, l'image est recadrée et centrée si nécessaire
- Si Remove_animation est vrai, toute animation dans l'image (par exemple, GIF) sera supprimée

```twig
{{ asset(image_path)|resize(width: width, height: height, remove_animation: bool) }}
```

:::info
Le fichier original n'est pas modifié et la version redimensionnée est enregistrée sous `/thumbnails/<width>x<height>/image.jpg`.
:::

:::tip
Les fichiers ICO sont supportés : la plus grande icône est redimensionnée et enregistrée dans un fichier ICO à icône unique (compressée en PNG). Les icônes stockées en BMP avec une profondeur de couleur autre que 24 ou 32 bits nécessitent l'extension PHP [Imagick](https://www.php.net/manual/fr/book.imagick.php) : à défaut, le fichier ICO original est conservé et un avertissement est journalisé.
:::

_Exemples :_

```twig
{{ asset(page.image)|resize(300) }}
{# equivalent to: #}
{{ asset(page.image)|resize(width: 300) }}
{# resizes to 300px width, height auto-calculated to preserve aspect ratio #}
{{ asset(page.image)|resize(height: 200) }}
{# resizes to 300px width and 200px height, and crops if necessary #}
{{ asset(page.image)|resize(300, 200) }}
{# removes any animation from the image #}
{{ asset(page.image)|resize(width: 1200, height: 630, remove_animation: true) }}
```

## cover

Redimensionne une image à une largeur et une hauteur spécifiées, en la recadrant si nécessaire.

:::warning
Le filtre `cover` est obsolète depuis la version ++8.77++ et sera supprimé dans les versions futures. Utilisez plutôt le filtre [`resize`](#resize), avec les paramètres de largeur et de hauteur.
:::

```twig
{{ asset(image_path)|cover(width, height) }}
```

_Exemple:_

```twig
{{ asset(page.image)|cover(1200, 630) }}
```

## maskable

Ajoute un remplissage, en pourcentages, à une image pour la rendre masquable.

```twig
{{ asset(image_path)|maskable(padding) }}
```

_Exemple:_

```twig
{{ asset('icon.png')|maskable }}
```

## webp

Convertit une image au format [WebP](https://developers.google.com/speed/webp).

_Exemple:_

```twig
<picture>
    <source type="image/webp" srcset="{{ asset(image_path)|webp }}">
    <img src="{{ url(asset(image_path)) }}" width="{{ asset(image_path).width }}" height="{{ asset(image_path).height }}" alt="">
</picture>
```

## avif

Convertit une image au format [AVIF](https://github.com/AOMediaCodec/libavif).

_Exemple:_

```twig
<picture>
    <source type="image/avif" srcset="{{ asset(image_path)|avif }}">
    <img src="{{ url(asset(image_path)) }}" width="{{ asset(image_path).width }}" height="{{ asset(image_path).height }}" alt="">
</picture>
```

## lqip

Renvoie un [espace réservé pour une image de faible qualité](https://www.guypo.com/introducing-lqip-low-quality-image-placeholders) (100 x 100 px, flou à 50 %) comme URL de données.

```twig
{{ asset(image_path)|lqip }}
```

## dominant_color

Renvoie la [couleur hexadécimale](https://developer.mozilla.org/en-US/docs/Web/CSS/hex-color) dominante d'une image.

```twig
{{ asset(image_path)|dominant_color }}
```
