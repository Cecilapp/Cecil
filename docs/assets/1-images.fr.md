<!--
title: "Images"
description: "Redimensionnez, recadrez et convertissez les images, générez des images responsives et des placeholders."
date: 2026-05-26
updated: 2026-10-16
-->
# Images

Cecil fournit des fonctions et filtres Twig pour redimensionner, recadrer et convertir des images, et pour générer des images responsives et des images de substitution.

## image_srcset

Construit l’attribut HTML img `srcset` (responsive) d’un asset image.

```twig
{{ image_srcset(asset) }}
```

_Exemple :_

```twig
{% set asset = asset(image_path) %}
<img src="{{ url(asset) }}" width="{{ asset.width }}" height="{{ asset.height }}" alt="" class="asset" srcset="{{ image_srcset(asset) }}" sizes="{{ image_sizes('asset') }}">
```

## image_sizes

Renvoie l’attribut HTML img `sizes` en fonction d’un nom de classe CSS (voir [`assets.images.responsive.sizes`](../configuration/6-assets.fr.md#assets-images)).

Il doit être utilisé conjointement avec la fonction [`image_srcset`](#image-srcset) (voir l’exemple ci-dessus).

```twig
{{ image_sizes('class') }}
```

## image_from_website

Construit l’élément HTML img à partir de l’URL d’un site web, en extrayant son image d’illustration.
Renvoie `null` si aucune image n’est trouvée.

```twig
{{ image_from_website('url', {attributes}, {options}) }}
```

Les candidats sont recherchés dans le HTML de la page dans l’ordre suivant ; le premier qui peut être téléchargé en tant qu’image est utilisé :

1. Open Graph : `og:image:secure_url`, `og:image`, `og:image:url`
2. Twitter : `twitter:image`, `twitter:image:src`
3. `<link rel="image_src">`
4. Microdata : `itemprop="image"`
5. JSON-LD : propriété `image`
6. Premier élément `<img>` de `<article>`, `<main>` ou `<body>`
7. `<link rel="apple-touch-icon">`
8. `<link rel="icon">`

Les URL relatives sont résolues par rapport à `<base href>` ou à l’URL de la page.

L’URL de l’image trouvée et l’image téléchargée sont mises en cache (voir [`cache.assets.remote.ttl`](../configuration/9-cache.fr.md#cache-assets-remote-ttl)).

Options :

- `fallback` : chemin (ou URL) de l’image utilisée si aucune image n’est trouvée
- autres options de [`image`](../templates/reference/1-functions.fr.md#html) (ex. : `responsive`, `formats`)

_Exemples :_

```twig
{{ image_from_website('https://example.com/page-with-image.html') }}

{# avec une image de repli #}
{{ image_from_website('https://example.com/', {alt: 'Illustration'}, {fallback: 'images/default.png'}) }}
```

## resize

Redimensionne une image à une largeur (en pixels) et/ou une hauteur (en pixels) spécifiées.

- Si seule la largeur est spécifiée, la hauteur est calculée pour conserver le rapport hauteur/largeur
- Si seule la hauteur est spécifiée, la largeur est calculée pour conserver le rapport hauteur/largeur
- Si la largeur et la hauteur sont spécifiées, l’image est redimensionnée pour tenir dans ces dimensions, puis recadrée et centrée si nécessaire
- Si `remove_animation` vaut `true`, toute animation de l’image (ex. : GIF) est supprimée

```twig
{{ asset(image_path)|resize(width: width, height: height, remove_animation: bool) }}
```

:::info
Le fichier original n’est pas modifié : la version redimensionnée est enregistrée sous `/thumbnails/<width>x<height>/<path>` (préfixé par [`assets.target`](../configuration/6-assets.fr.md#assets-target) s’il est défini).
:::

:::tip
Les fichiers ICO sont pris en charge : la plus grande icône est redimensionnée et enregistrée dans un fichier ICO à icône unique (compressée en PNG). Les icônes stockées en BMP avec une profondeur de couleur autre que 24 ou 32 bits nécessitent l’extension PHP [Imagick](https://www.php.net/manual/fr/book.imagick.php) : à défaut, le fichier ICO original est conservé et un avertissement est journalisé.
:::

_Exemples :_

```twig
{{ asset(page.image)|resize(300) }}
{# équivalent à : #}
{{ asset(page.image)|resize(width: 300) }}
{# redimensionne à 200px de hauteur, largeur calculée pour conserver le rapport hauteur/largeur #}
{{ asset(page.image)|resize(height: 200) }}
{# redimensionne à 300px de largeur et 200px de hauteur, et recadre si nécessaire #}
{{ asset(page.image)|resize(300, 200) }}
{# supprime toute animation de l’image #}
{{ asset(page.image)|resize(width: 1200, height: 630, remove_animation: true) }}
```

## cover

Redimensionne une image à une largeur et une hauteur spécifiées, en la recadrant si nécessaire.

:::warning
Le filtre `cover` est obsolète depuis la version ++8.77++ et sera supprimé dans une version future. Utilisez plutôt le filtre [`resize`](#resize), avec les paramètres de largeur et de hauteur.
:::

```twig
{{ asset(image_path)|cover(width, height) }}
```

_Exemple :_

```twig
{{ asset(page.image)|cover(1200, 630) }}
```

## maskable

Ajoute une marge intérieure, en pourcentage de la taille de l’image (`20` par défaut), pour rendre l’image masquable.

```twig
{{ asset(image_path)|maskable(padding) }}
```

_Exemple :_

```twig
{{ asset('icon.png')|maskable }}
```

## webp

Convertit une image au format [WebP](https://developers.google.com/speed/webp), avec une qualité optionnelle (par défaut : [`assets.images.quality`](../configuration/6-assets.fr.md#assets-images)).

```twig
{{ asset(image_path)|webp(quality) }}
```

_Exemple :_

```twig
<picture>
    <source type="image/webp" srcset="{{ asset(image_path)|webp }}">
    <img src="{{ url(asset(image_path)) }}" width="{{ asset(image_path).width }}" height="{{ asset(image_path).height }}" alt="">
</picture>
```

## avif

Convertit une image au format [AVIF](https://github.com/AOMediaCodec/libavif), avec une qualité optionnelle (par défaut : [`assets.images.quality`](../configuration/6-assets.fr.md#assets-images)).

```twig
{{ asset(image_path)|avif(quality) }}
```

_Exemple :_

```twig
<picture>
    <source type="image/avif" srcset="{{ asset(image_path)|avif(60) }}">
    <img src="{{ url(asset(image_path)) }}" width="{{ asset(image_path).width }}" height="{{ asset(image_path).height }}" alt="">
</picture>
```

:::info
La prise en charge de WebP et d’AVIF dépend du pilote d’images disponible (Imagick, libvips ou GD).
:::

## lqip

Renvoie un [espace réservé pour une image de faible qualité](https://www.guypo.com/introducing-lqip-low-quality-image-placeholders) (100 px de large, flouté) sous forme d’URL de données (data URL).

```twig
{{ asset(image_path)|lqip }}
```

## dominant_color

Renvoie la [couleur hexadécimale](https://developer.mozilla.org/fr/docs/Web/CSS/hex-color) dominante d’une image.

```twig
{{ asset(image_path)|dominant_color }}
```
