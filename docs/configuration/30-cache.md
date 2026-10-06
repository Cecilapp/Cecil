<!--
title: "Cache"
description: "Assets, templates and translations cache."
date: 2021-05-07
updated: 2026-10-05
-->
# Cache

Cache options.

## cache.enabled

Cache is enabled by default (`true`), but you can disable it with:

```yaml
cache:
  enabled: false
```

:::warning
It’s not recommended to disable the cache for performance reasons.
:::

## cache.dir

Directory where cache files are stored (`.cache` by default).

```yaml
cache:
  dir: '.cache'
```

:::info
The cache directory is relative to the site directory, but you can use an absolute path: it can be useful to store the cache in a shared directory.
:::

## cache.assets

Assets cache options.

### cache.assets.ttl

Time to live of assets cache in seconds (`null` by default = no expiration).

```yaml
cache:
  assets:
    ttl: ~
```

### cache.assets.remote.ttl

Time to live of remote assets cache in seconds (7 days by default).

```yaml
cache:
  assets:
    remotes:
      ttl: 604800 # 7 days
```

## cache.templates

Disables templates cache with `false` (`true` by default).

```yaml
cache:
  templates: true
```

:::info
See [templates cache documentation](../templates/17-cache.md) for more details.
:::

## cache.translations

Disables translations cache  with `false` (`true` by default).

```yaml
cache:
  translations: true
```

---
