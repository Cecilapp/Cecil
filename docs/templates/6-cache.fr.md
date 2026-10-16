<!--
title: "Cache"
description: "Cache des templates et cache de fragments."
date: 2026-05-26
updated: 2026-10-16
-->
# Cache

Cecil utilise un système de cache pour accélérer le processus de génération ; il peut être désactivé ou vidé.

Il existe trois types de cache impliqués dans le rendu des templates : les templates, les [assets](../assets/index.fr.md#asset) et les [traductions](5-localization.fr.md#fichiers-de-traduction).

## Vider le cache

Vous pouvez vider le cache avec les commandes suivantes :

```bash
php cecil.phar cache:clear               # clear all caches
php cecil.phar cache:clear:assets        # clear assets cache
php cecil.phar cache:clear:templates     # clear templates cache
php cecil.phar cache:clear:translations  # clear translations cache
```

:::important
En pratique, vous n’avez pas besoin de vider le cache manuellement : Cecil le fait pour vous en cas de besoin (par exemple lorsque des fichiers changent).
:::

## Cache de fragments

Cecil fournit un moyen de mettre en cache des parties du rendu des templates pour éviter de générer plusieurs fois le même contenu partiel.

Pour utiliser le cache de _fragments_, enveloppez le contenu que vous souhaitez mettre en cache avec la balise [`cache`](https://twig.symfony.com/doc/tags/cache.html).

```twig
{% cache 'unique-key' %}
  {# cacheable content #}
{% endcache %}
```

:::tip
Il est recommandé d’utiliser la fonction [`cache_key`](reference/1-functions.fr.md#cache-key) pour être sûr d’avoir une clé de cache unique pour chaque contenu que vous souhaitez mettre en cache.
:::

:::warning
Le cache de _fragments_ est persistant : si la clé de cache est trop générique, vous risquez d’afficher un contenu erroné.
:::

Pour vider uniquement le cache de fragments, utilisez la commande suivante :

```bash
php cecil.phar cache:clear:templates --fragments
```

## Désactiver le cache

Vous pouvez désactiver le cache avec la [configuration](../configuration/9-cache.fr.md).

:::warning
La désactivation du cache peut ralentir le processus de génération, ce n’est donc pas recommandé.

Lors du développement local, si vous devez vider le cache avant chaque génération, vous pouvez utiliser l’option suivante :

```bash
php cecil.phar serve --clear-cache          # clear all caches
php cecil.phar serve --clear-cache=<regex>  # clear cache for cache keys matching the regular expression <regex>
```

Exemple :

```bash
php cecil.phar serve --clear-cache=css  # clear cache for all CSS files
```

:::
