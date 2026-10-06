<!--
title: "Cache"
description: "Cache des assets, des templates et des traductions."
date: 2026-03-27
updated: 2026-10-05
-->
# Cache

Options de cache.

## cache.enabled

Le cache est activé par défaut (`true`), mais vous pouvez le désactiver avec :

```yaml
cache:
  enabled: false
```

:::warning
Il n’est pas recommandé de désactiver le cache pour des raisons de performance.
:::

## cache.dir

Répertoire où les fichiers de cache sont stockés (`.cache` par défaut).

```yaml
cache:
  dir: '.cache'
```

:::info
Le répertoire de cache est relatif au répertoire du site, mais vous pouvez utiliser un chemin absolu : cela peut être utile pour stocker le cache dans un répertoire partagé.
:::

## cache.assets

Options du cache des ressources.

### cache.assets.ttl

Temps de vie du cache des ressources en secondes (`null` par défaut = aucune expiration).

```yaml
cache:
  assets:
    ttl: ~
```

### cache.assets.remote.ttl

Temps de vie du cache des ressources distantes en secondes (7 jours par défaut).

```yaml
cache:
  assets:
    remotes:
      ttl: 604800 # 7 jours
```

## cache.templates

Désactive le cache des templates avec `false` (`true` par défaut).

```yaml
cache:
  templates: true
```

:::info
Voir la [documentation du cache des templates](../templates/17-cache.fr.md) pour plus de détails.
:::

## cache.translations

Désactive le cache des traductions avec `false` (`true` par défaut).

```yaml
cache:
  translations: true
```

---
