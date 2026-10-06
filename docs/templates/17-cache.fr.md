<!--
title: "Cache"
description: "Cache des templates et cache de fragments."
date: 2026-05-26
updated: 2026-10-05
-->
# Cache

Cecil utilise un système de cache pour accélérer le processus de génération, il peut être désactivé ou effacé.

Il existe trois types de cache dans le cas du rendu des templates : les templates eux-mêmes, [assets](../assets/index.fr.md#asset) et [translations](16-localization.fr.md#fichiers-de-traduction).

## Vider le cache

Vous pouvez vider le cache avec les commandes suivantes :

```bash
php cecil.phar cache:clear               # clear all caches
php cecil.phar cache:clear:assets        # clear assets cache
php cecil.phar cache:clear:templates     # clear templates cache
php cecil.phar cache:clear:translations  # clear translations cache
```

:::important
En pratique, vous n'avez pas besoin de vider le cache manuellement, Cecil le fait pour vous en cas de besoin (par exemple lorsque des fichiers changent).
:::

## Fragments de cache

Cecil fournit un moyen de mettre en cache des parties du rendu des templates pour éviter de restituer plusieurs fois le même contenu partiel.

Pour utiliser les _fragments_ de cache, vous devez envelopper le contenu que vous souhaitez mettre en cache avec la balise [`cache`](https://twig.symfony.com/doc/tags/cache.html).

```twig
{% cache 'unique-key' %}
  {# cacheable content #}
{% endcache %}
```

:::tip
Vous devez utiliser la fonction [`cache_key`](reference/12-functions.fr.md#cache-key) pour être sûr d'avoir une clé de cache unique pour chaque contenu que vous souhaitez mettre en cache.
:::

:::warning
Les _fragments_ de cache sont persistants, donc si la clé de cache est trop générique, vous risquez de vous retrouver avec un mauvais contenu affiché.
:::

Pour vider uniquement le cache des fragments, vous pouvez utiliser la commande suivante :

```bash
php cecil.phar cache:clear:templates --fragments
```

## Désactiver le cache

Vous pouvez désactiver le cache avec la [configuration](../configuration/30-cache.fr.md).

:::warning
La désactivation du cache peut ralentir le processus de génération, ce n'est donc pas recommandé.

Lors du développement local, si vous devez vider le cache avant chaque génération, vous pouvez utiliser l'option suivante :

```bash
php cecil.phar serve --clear-cache          # clear all caches
php cecil.phar serve --clear-cache=<regex>  # clear cache for cache key matches with the regular expression <regex>
```

Exemple:

```bash
php cecil.phar serve --clear-cache=css  # clear cache for all CSS files
```

:::
