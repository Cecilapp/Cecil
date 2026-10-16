<!--
title: "Assets"
description: "Dossier des assets, compilation, minification, images et CDN."
date: 2026-03-27
updated: 2026-10-16
-->
# Assets

Gestion des assets (images, fichiers CSS et JS).

## assets.dir

Répertoire source des assets (`assets` par défaut).

```yaml
assets:
  dir: assets
```

## assets.target

Répertoire où les assets distants et redimensionnés sont enregistrés (racine par défaut).

```yaml
assets:
  target: ''
```

## assets.fingerprint

Active le fingerprinting (cache busting) pour les fichiers d’assets (`true` par défaut).

```yaml
assets:
  fingerprint: true
```

## assets.compile

Options de compilation des fichiers [Sass](https://sass-lang.com). Voir la [documentation de scssphp](https://scssphp.github.io/scssphp/docs/#output-formatting) pour les détails des options.

```yaml
assets:
  compile:
    style: expanded      # style de compilation (`expanded` ou `compressed`, `expanded` par défaut)
    import: [sass, scss] # liste des chemins importés (`[sass, scss, node_modules]` par défaut)
    sourcemap: false     # active les sourcemaps en mode debug (`false` par défaut)
    variables: {}        # variables prédéfinies, sous la forme `nom: valeur` (vide par défaut)
```

:::info
`sourcemap` sert à déboguer la compilation SCSS ([mode debug](1-site.fr.md#debug) requis).
:::

## assets.minify

Active la minification CSS et JS (`true` par défaut).

```yaml
assets:
  minify: true
```

## assets.images

Gestion des images.

```yaml
assets:
  images:
    optimize: false # active l’optimisation d’images avec JpegOptim, Optipng, Pngquant 2, SVGO 1, Gifsicle, cwebp, avifenc (`false` par défaut)
    quality: 75     # qualité d’image utilisée pour l’optimisation et le redimensionnement (`75` par défaut)
    responsive:
      widths: [480, 640, 768, 1024, 1366, 1600, 1920] # largeurs d’image pour l’attribut `srcset`
      sizes:
        default: '100vw' # attribut `sizes` par défaut (`100vw` par défaut)
      densities: [1, 2, 3] # densités de pixels de l’attribut `srcset`
```

## assets.images.cdn

L’URL des images peut être facilement remplacée par l’`url` d’un CDN.

```yaml
assets:
  images:
    cdn:
      enabled: false  # active le CDN d’images (`false` par défaut)
      svg: false      # inclut les images SVG (`false` par défaut)
      canonical: true # `image_url` est canonique (au lieu d’un chemin relatif) (`true` par défaut)
      remote: true    # prend aussi en charge les images distantes (`true` par défaut)
      account: 'xxxx' # compte du fournisseur
      url: 'https://provider.tld/%account%/%image_url%?w=%width%&q=%quality%&format=%format%'
```

`url` est un motif qui contient des variables :

- `%account%` remplacé par l’option `assets.images.cdn.account`
- `%image_url%` remplacé par l’URL canonique de l’image ou par `path`
- `%width%` remplacé par la largeur de l’image
- `%quality%` remplacé par l’option `assets.images.quality`
- `%format%` remplacé par le format de l’image

Voir les [**fournisseurs CDN**](../assets/3-cdn-providers.fr.md).

## assets.remote.useragent

User agent utilisé pour télécharger les assets distants.

```yaml
assets:
  remote:
    useragent:
      default: <string> # user agent des requêtes HTTP
```

:::info
Pour un asset donné, le user agent peut être remplacé avec l’option `useragent` de la fonction [`asset()`](../assets/index.fr.md#asset).
:::

## assets.remote.timeout

Délai d’expiration du téléchargement des assets distants, en secondes (`30` par défaut).

```yaml
assets:
  remote:
    timeout: 30
```

---
