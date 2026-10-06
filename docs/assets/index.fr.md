<!--
title: "Assets"
description: "Manipulez les assets (images, feuilles de style, scripts, etc.) avec la fonction asset() : traitement, optimisation et empreinte."
date: 2026-05-26
updated: 2026-10-05
weight: 4
sortby: weight
-->
# Assets

## asset

Un actif est une ressource utilisable dans des templates, comme CSS, JavaScript, image, audio, vidéo, etc.

La fonction `asset()` crée un objet _asset_ à partir d'un chemin de fichier, d'un tableau de chemins de fichiers (bundle) ou d'une URL (fichier distant), et est traité (minifié, empreinte digitale, etc.) selon la [configuration](../configuration/27-assets.fr.md).

Les fichiers de ressources doivent être stockés dans le répertoire `assets/` (ou `static/`).

```twig
{{ asset(path, {options}) }}
```

| Options            | Descriptif                                                                                                | Tapez   | Par défaut                   |
| ------------------ | --------------------------------------------------------------------------------------------------------- | ------- | ---------------------------- |
| nom de fichier     | Enregistrez le bundle sous un nom de fichier personnalisé.                                                | chaîne  | `styles.css` ou `scripts.js` |
| ignore_missing     | N'arrêtez pas la construction si le fichier n'est pas trouvé.                                             | booléen | `false`                      |
| empreinte digitale | Ajoutez un hachage de contenu au nom du fichier.                                                          | booléen | `true`                       |
| réduire            | Compressez CSS ou JavaScript.                                                                             | booléen | `true`                       |
| optimiser          | Compresser l'image.                                                                                       | booléen | `false`                      |
| repli              | Chargez un actif local si le fichier distant est introuvable.                                             | chaîne  | ``                           |
| agent utilisateur  | Clé de l'agent utilisateur (Voir [Configuration des actifs](../configuration/27-assets.fr.md#assets-remote-useragent)). | chaîne  | `default`                    |

:::tip
Vous pouvez utiliser [filters](../templates/reference/14-filters.fr.md) pour manipuler les actifs.
:::

:::info
Vous n'avez pas besoin de vider le [cache](../templates/17-cache.fr.md) après avoir modifié un actif : le cache est automatiquement vidé lorsque le fichier est modifié ou lorsque le nom du fichier est changé.
:::

_Exemples :_

```twig
{# CSS #}
{{ asset('styles.css') }}
{# CSS bundle #}
{{ asset(['poole.css', 'hyde.css'], {filename: styles.css}) }}
{# JavaScript #}
{{ asset('scripts.js') }}
{# image #}
{{ asset('image.jpeg') }}
{# audio #}
{{ asset('audio.mp3') }}
{# video #}
{{ asset('video.mp4') }}
{# remote file #}
{{ asset('https://cdnjs.cloudflare.com/ajax/libs/anchor-js/4.3.1/anchor.min.js', {minify: false}) }}
{# with filter #}
{{ asset('styles.css')|minify }}
{{ asset('styles.scss')|to_css|minify }}
```

### Asset attributes

Les ressources créées avec la fonction `asset()` exposent certains attributs utiles.

Commun:

- `file` : chemin du système de fichiers
- `missing` : `true` si le fichier n'est pas trouvé mais que le fichier manquant est autorisé
- `path` : chemin public
- `ext` : extension de fichier
- `type` : type de média (ex. : `image`)
- `subtype` : sous-type de média (ex. : `image/jpeg`)
- `size` : taille en octets
- `content` : contenu du fichier
- `hash` : hachage du contenu du fichier (md5)
- `dataurl` : URL de données encodées en Base64
- `integrity` : hachage d'intégrité

Télécommande:

- `url` : URL du fichier distant

Paquet:

- `files` : tableau du chemin du système de fichiers en cas de bundle

Image:

- `width` : largeur de l'image en pixels
- `height` : hauteur de l'image en pixels
- `exif` : données EXIF ​​de l'image sous forme de tableau

Audio :

- `duration` : durée en secondes.microsecondes
- `bitrate` : débit en bps
- `channel` : 'stéréo', 'dual_mono', 'joint_stereo' ou 'mono'

Vidéo:

- `duration` : durée en secondes
- `width` : largeur en pixels
- `height` : hauteur en pixels

_Exemples :_

```twig
{# image width in pixels #}
{{ asset('image.png').width }}px
{# photo's date in seconds #}
{{ asset('photo.jpeg').exif.EXIF.DateTimeOriginal|date('U') }}
{# audio duration in seconds #}
{{ asset('song.mp3').duration|round }} s
{# video duration in seconds #}
{{ asset('movie.mp4').duration|round }} s
{# file integrity hash #}
{% set integrity = asset('styles.scss').integrity %}
```

## integrity

Crée le hachage (`sha384`) d'un fichier (à partir d'un actif ou d'un chemin).

```twig
{{ integrity(asset) }}
```

Utilisé pour SRI ([Intégrité des sous-ressources](https://developer.mozilla.org/fr/docs/Web/Security/Subresource_Integrity)).

_Exemple:_

```twig
{{ integrity('styles.css') }}
{# sha384-oGDH3qCjzMm/vI+jF4U5kdQW0eAydL8ZqXjHaLLGduOsvhPRED9v3el/sbiLa/9g #}
```
