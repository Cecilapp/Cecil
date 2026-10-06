<!--
title: "Cache"
description: "Templates cache and fragments cache."
date: 2021-05-07
updated: 2026-10-05
-->
# Cache

Cecil uses a cache system to speed up the generation process, it can be disabled or cleared.

There are three cache types involved in template rendering: templates, [assets](../assets/index.md#asset), and [translations](5-localization.md#translation-files).

## Clear cache

You can clear the cache with the following commands:

```bash
php cecil.phar cache:clear               # clear all caches
php cecil.phar cache:clear:assets        # clear assets cache
php cecil.phar cache:clear:templates     # clear templates cache
php cecil.phar cache:clear:translations  # clear translations cache
```

:::important
In practice you don't need to clear the cache manually, Cecil does it for you when needed (e.g. when files change).
:::

## Fragments cache

Cecil provides a way to cache parts of templates rendering to avoid re-rendering the same partial content multiple times.

To use _fragments_ cache, you must wrap the content you want to cache with the [`cache` tag](https://twig.symfony.com/doc/tags/cache.html).

```twig
{% cache 'unique-key' %}
  {# cacheable content #}
{% endcache %}
```

:::tip
You should use the [`cache_key` function](reference/1-functions.md#cache-key) to be sure to have a unique cache key for each content you want to cache.
:::

:::warning
_Fragments_ cache is persistent, so if the cache key is too generic, you may end up with wrong content displayed.
:::

To clear fragments cache only, you can use the following command:

```bash
php cecil.phar cache:clear:templates --fragments
```

## Disable cache

You can disable cache with the [configuration](../configuration/9-cache.md).

:::warning
Disabling cache can slow down the generation process, so it's not recommended.

During local development, if you need to clear cache before each generation, you can use the following option:

```bash
php cecil.phar serve --clear-cache          # clear all caches
php cecil.phar serve --clear-cache=<regex>  # clear cache for cache key matches with the regular expression <regex>
```

Example:

```bash
php cecil.phar serve --clear-cache=css  # clear cache for all CSS files
```

:::
