<!--
title: "Fournisseurs de CDN"
description: "Exemples de configuration de fournisseurs de CDN d’images (Cloudinary, Cloudimage, TwicPics, imgix, Netlify Image CDN)."
date: 2023-10-23
updated: 2026-10-16
path: documentation/assets/fournisseurs-cdn
-->
# Fournisseurs de CDN

Exemples de configuration de fournisseurs de CDN d’images (voir l’option [`assets.images.cdn`](../configuration/6-assets.fr.md#assets-images-cdn)).

:::tip
Les images SVG ne sont envoyées au CDN que si le fournisseur les prend en charge et que `svg: true` est défini ; les images distantes sont prises en charge, sauf si `remote: false` est défini.
:::

## Cloudinary

<https://cloudinary.com>

```yaml
assets:
  images:
    cdn:
      enabled: true
      account: 'xxxx'
      url: 'https://res.cloudinary.com/%account%/image/fetch/c_limit,w_%width%,q_%quality%,f_%format%,d_default/%image_url%'
```

## Cloudimage

<https://www.cloudimage.io>

```yaml
assets:
  images:
    cdn:
      enabled: true
      account: 'xxxx'
      url: 'https://%account%.cloudimg.io/%image_url%?w=%width%&q=%quality%&force_format=%format%'
```

## TwicPics

<https://www.twicpics.com>

```yaml
assets:
  images:
    cdn:
      enabled: true
      account: 'xxxx'
      canonical: false
      remote: false
      url: 'https://%account%.twic.pics/%image_url%?twic=v1/resize=%width%/quality=%quality%/output=%format%'
```

Dans le tableau de bord TwicPics, définissez la **Source URL** sur l’URL de base (`baseurl`) de votre site.

## imgix

<https://imgix.com>

```yaml
assets:
  images:
    cdn:
      enabled: true
      account: 'xxxx'
      canonical: false
      remote: false
      url: 'https://%account%.imgix.net/%image_url%?w=%width%&q=%quality%&fm=%format%'
```

Dans le tableau de bord imgix, définissez la **Base URL** de la source sur l’URL de base (`baseurl`) de votre site.

## Netlify Image CDN

<https://docs.netlify.com/image-cdn/overview/>

```yaml
assets:
  images:
    cdn:
      enabled: true
      canonical: false
      url: '/.netlify/images?url=%image_url%&w=%width%&fm=%format%'
```

### Exécution en local

:::steps
#### Installer Netlify CLI

```bash
npm install netlify-cli -g
netlify link
```

`netlify.toml` :

```toml
[dev]
  targetPort = 8000
```

#### Démarrer le serveur local

```bash
php cecil.phar serve & netlify dev
```

Sous Windows, lancez `php cecil.phar serve` et `netlify dev` dans deux terminaux distincts.

Ouvrez <http://localhost:8888>
:::
