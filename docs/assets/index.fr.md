<!--
title: "Assets"
description: "Manipulez les assets (images, feuilles de style, scripts, etc.) avec la fonction asset() : traitement, optimisation et empreinte."
date: 2026-05-26
updated: 2026-10-16
weight: 4
sortby: weight
-->
# Assets

Les assets sont des fichiers (feuilles de style, scripts, images, polices, etc.) que Cecil peut traiter et optimiser depuis vos templates.

## asset

Un asset est une ressource utilisable dans les templates : CSS, JavaScript, image, audio, vidéo, etc.

La fonction `asset()` crée un objet _asset_ à partir d’un chemin de fichier, d’un tableau de chemins de fichiers (bundle) ou d’une URL (fichier distant). L’asset est ensuite traité (compilé, minifié, doté d’une empreinte, etc.) selon la [configuration](../configuration/6-assets.fr.md).

Les fichiers sont recherchés dans le répertoire des assets (`assets/` par défaut), puis dans le répertoire des fichiers statiques (`static/` par défaut) et dans les thèmes.

```twig
{{ asset(path, {options}) }}
```

| Option         | Description                                                                                                                  | Type    | Par défaut                                        |
| -------------- | ---------------------------------------------------------------------------------------------------------------------------- | ------- | ------------------------------------------------- |
| filename       | Enregistre le bundle sous un nom de fichier personnalisé.                                                                    | chaîne  | `styles.css` ou `scripts.js` (bundle)             |
| ignore_missing | N’interrompt pas la génération si le fichier est introuvable.                                                                | booléen | `false`                                           |
| fingerprint    | Ajoute l’empreinte (hash) du contenu au nom du fichier.                                                                      | booléen | valeur de `assets.fingerprint` (`true`)           |
| minify         | Minifie le CSS ou le JavaScript.                                                                                             | booléen | valeur de `assets.minify` (`true`)                |
| optimize       | Optimise l’image.                                                                                                            | booléen | valeur de `assets.images.optimize` (`false`)      |
| fallback       | Asset local à charger si le fichier distant est introuvable.                                                                 | chaîne  | vide                                              |
| useragent      | Chaîne d’agent utilisateur envoyée lors du téléchargement d’un fichier distant (voir la [configuration des assets](../configuration/6-assets.fr.md#assets-remote-useragent)). | chaîne  | valeur de `assets.remote.useragent.default` |
| language       | Code de langue utilisé pour rechercher d’abord une version localisée du fichier (ex. : `image.fr.jpg` pour `image.jpg`).     | chaîne  | `null`                                            |

:::tip
Les assets peuvent être manipulés avec des filtres dédiés : voir [Images](1-images.fr.md) et [CSS, JavaScript et traitements](2-processing.fr.md).

Pour afficher un asset sous forme d’élément HTML (`<link>`, `<script>`, `<img>`, `<audio>`, `<video>`), utilisez la fonction [`html()`](../templates/reference/1-functions.fr.md#html).
:::

:::info
Inutile de vider le [cache](../templates/6-cache.fr.md) après avoir modifié un asset : le cache est automatiquement invalidé lorsque le fichier est modifié ou que son nom change.
:::

_Exemples :_

```twig
{# CSS #}
{{ asset('styles.css') }}
{# bundle CSS #}
{{ asset(['poole.css', 'hyde.css'], {filename: 'styles.css'}) }}
{# JavaScript #}
{{ asset('scripts.js') }}
{# image #}
{{ asset('image.jpeg') }}
{# image localisée (recherche d’abord « image.fr.jpeg ») #}
{{ asset('image.jpeg', {language: 'fr'}) }}
{# audio #}
{{ asset('audio.mp3') }}
{# vidéo #}
{{ asset('video.mp4') }}
{# fichier distant #}
{{ asset('https://cdnjs.cloudflare.com/ajax/libs/anchor-js/4.3.1/anchor.min.js', {minify: false}) }}
{# avec un filtre #}
{{ asset('styles.css')|minify }}
{{ asset('styles.scss')|to_css|minify }}
```

### Attributs d’un asset

Les assets créés avec la fonction `asset()` exposent des attributs utiles.

Communs :

- `file` : chemin dans le système de fichiers
- `missing` : `true` si le fichier est introuvable et que `ignore_missing` est activé
- `path` : chemin public
- `ext` : extension du fichier
- `type` : type de média (ex. : `image`)
- `subtype` : sous-type de média (ex. : `image/jpeg`)
- `size` : taille en octets
- `content` : contenu du fichier
- `hash` : empreinte du contenu du fichier (xxHash 128)
- `dataurl` : URL de données (data URL) encodée en Base64
- `integrity` : empreinte d’intégrité

Distant :

- `url` : URL du fichier distant

Bundle :

- `files` : tableau des chemins de fichiers, dans le cas d’un bundle

Image :

- `width` : largeur de l’image en pixels
- `height` : hauteur de l’image en pixels
- `exif` : données EXIF de l’image sous forme de tableau

Audio :

- `duration` : durée en secondes.microsecondes
- `getAudio.bitrate` : débit en bps
- `getAudio.channel` : `stereo`, `dual_mono`, `joint_stereo` ou `mono`

Vidéo :

- `duration` : durée en secondes
- `width` : largeur en pixels
- `height` : hauteur en pixels

_Exemples :_

```twig
{# largeur de l’image en pixels #}
{{ asset('image.png').width }}px
{# date de la photo en timestamp Unix #}
{{ asset('photo.jpeg').exif.EXIF.DateTimeOriginal|date('U') }}
{# durée de l’audio en secondes #}
{{ asset('song.mp3').duration|round }} s
{# débit de l’audio #}
{{ asset('song.mp3').getAudio.bitrate }} bps
{# durée de la vidéo en secondes #}
{{ asset('movie.mp4').duration|round }} s
{# empreinte d’intégrité du fichier #}
{% set integrity = asset('styles.scss').integrity %}
```

## integrity

Crée l’empreinte (`sha384` par défaut) d’un fichier (à partir d’un asset ou d’un chemin).

```twig
{{ integrity(asset, algo) }}
```

Utilisée pour le SRI ([Subresource Integrity](https://developer.mozilla.org/fr/docs/Web/Security/Subresource_Integrity)).

_Exemples :_

```twig
{{ integrity('styles.css') }}
{# sha384-oGDH3qCjzMm/vI+jF4U5kdQW0eAydL8ZqXjHaLLGduOsvhPRED9v3el/sbiLa/9g #}
{{ integrity('styles.css', 'sha512') }}
```

## Tests

[Tests](https://twig.symfony.com/doc/templates.html#test-operator) Twig pour vérifier les assets :

- `asset` : la variable est un asset
- `image_large` : l’asset est une image au format paysage d’au moins 600×315 pixels
- `image_square` : l’asset est une image dont la largeur et la hauteur sont égales

_Exemple :_

```twig
{% set image = asset(page.image) %}
{% if image is image_large %}
  <meta name="twitter:card" content="summary_large_image">
{% endif %}
```
