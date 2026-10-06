<!--
title: "Assets"
description: "Dossier des assets, compilation, minification, images et CDN."
date: 2026-03-27
updated: 2026-10-05
-->
# Assets

Gestion des ressources (images, fichiers CSS et JS).

## assets.dir

Répertoire source des ressources (`assets` par défaut).

```yaml
assets:
  dir: assets
```

## assets.target

Répertoire où les fichiers de ressources distants et redimensionnés sont enregistrés (`root` par défaut).

```yaml
assets:
  target: ''
```

## assets.fingerprint

Active le fingerprinting (cache busting) pour les fichiers de ressources (`true` par défaut).

```yaml
assets:
  fingerprint: true
```

## assets.compile

Active la compilation des fichiers [Sass](https://sass-lang.com) (`true` par défaut). Voir la [documentation de scssphp](https://scssphp.github.io/scssphp/docs/#output-formatting) pour les détails des options.

```yaml
assets:
  compile:
    style: expanded      # style de compilation (`expanded` ou `compressed`, `expanded` par défaut)
    import: [sass, scss] # liste des chemins importés (`[sass, scss, node_modules]` par défaut)
    sourcemap: false     # active les sourcemaps en mode debug (`false` par défaut)
    variables: []        # liste de variables préconfigurées (vide par défaut)
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
    quality: 75     # qualité d’image pour `optimize` et `resize` (`75` par défaut)
    responsive:
      widths: [480, 640, 768, 1024, 1366, 1600, 1920] # largeurs d’image pour l’attribut `srcset`
      sizes:
        default: '100vw' # attribut `sizes` par défaut (`100vw` par défaut)
```

## assets.images.cdn

L’URL des ressources image peut être facilement remplacée par une `url` de CDN fournie.

```yaml
assets:
  images:
    cdn:
      enabled: false  # active Image CDN (`false` par défaut)
      canonical: true # `image_url` est canonique (au lieu d’un chemin relatif) (`true` par défaut)
      remote: true    # prend aussi en charge les images non locales (`true` par défaut)
      account: 'xxxx' # compte du fournisseur
      url: 'https://provider.tld/%account%/%image_url%?w=%width%&q=%quality%&format=%format%'
```

`url` est un modèle qui contient des variables :

- `%account%` remplacé par l’option `assets.images.cdn.account`
- `%image_url%` remplacé par l’URL canonique de l’image ou par `path`
- `%width%` remplacé par la largeur de l’image
- `%quality%` remplacé par l’option `assets.images.quality`
- `%format%` remplacé par le format de l’image

Voir les [**fournisseurs CDN**](../assets/3-cdn-providers.fr.md).

## assets.remote.useragent

User agent utilisé pour télécharger les ressources distantes.

```yaml
assets:
  remote:
    useragent:
      default: <string> # user agent par défaut
      useragent1: <string>
      useragent2: <string>
```
