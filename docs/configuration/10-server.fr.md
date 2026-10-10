<!--
title: "Serveur et optimisation"
description: "En-têtes du serveur local et optimisation de la sortie."
date: 2026-03-27
updated: 2026-10-10
path: documentation/configuration/serveur
-->
# Serveur et optimisation

Ces options configurent le serveur de prévisualisation local et l’optimisation des fichiers générés.

## Serveur

### server.headers

Vous pouvez définir des [en-têtes HTTP](https://developer.mozilla.org/docs/Glossary/Response_header) personnalisés, utilisés par le serveur d’aperçu local.

:::warning
Depuis la version ++8.38.0++, l’option `headers` a été déplacée dans la section `server.headers`.
:::

```yaml
server:
  headers:
    - path: <path> # chemin relatif, préfixé par un slash. Prend en charge le joker "*".
      headers:
        - key: <key>
          value: "<value>"
```

:::tip
C’est utile pour tester une [Content Security Policy](https://developer.mozilla.org/docs/Web/HTTP/CSP) ou `Cache-Control` personnalisée.
:::

_Exemple :_

```yaml
server:
  headers:
    - path: /*
      headers:
        - key: X-Frame-Options
          value: "SAMEORIGIN"
        - key: X-XSS-Protection
          value: "1; mode=block"
        - key: X-Content-Type-Options
          value: "nosniff"
        - key: Content-Security-Policy
          value: "default-src 'self'; object-src 'self'; img-src 'self'"
        - key: Strict-Transport-Security
          value: "max-age=31536000; includeSubDomains; preload"
    - path: /assets/*
      headers:
        - key: Cache-Control
          value: "public, max-age=31536000"
    - path: /foo.html
      headers:
        - key: Foo
          value: "bar"
```

---

## Optimisation

Les options d’optimisation permettent d’activer la compression des fichiers de sortie : HTML, CSS, JavaScript et images.

```yaml
optimize:
  enabled: false     # active l’optimisation des fichiers (`false` par défaut)
  html:
    enabled: true    # active l’optimisation des fichiers HTML
    ext: [html, htm]   # extensions de fichiers prises en charge
  css:
    enabled: true    # active l’optimisation des fichiers CSS
    ext: [css]         # extensions de fichiers prises en charge
  js:
    enabled: true    # active l’optimisation des fichiers JavaScript
    ext: [js]          # extensions de fichiers prises en charge
  images:
    enabled: true    # active l’optimisation des fichiers images
    ext: [jpeg, jpg, png, gif, webp, svg, avif] # extensions de fichiers prises en charge
```

Cette option est désactivée par défaut et peut être activée via :

```yaml
optimize: true
```

Une fois l’option globale activée, les 4 types de fichiers seront traités.  
Il est possible de désactiver chacun d’eux via `enabled: false` et de modifier l’extension des fichiers traités via `ext`.

:::tip
Il est également possible d’activer cette option via la CLI lors de l’utilisation des commandes "build" et "serve" avec l’option `--optimize`.
:::

:::important
Le compresseur d’**images** utilisera les binaires suivants s’ils sont présents sur le système : [JpegOptim](https://github.com/tjko/jpegoptim), [Optipng](http://optipng.sourceforge.net/), [Pngquant 2](https://pngquant.org/), [SVGO](https://github.com/svg/svgo), [Gifsicle](http://www.lcdf.org/gifsicle/), [cwebp](https://developers.google.com/speed/webp/docs/cwebp) et [avifenc](https://github.com/AOMediaCodec/libavif).
:::

---
