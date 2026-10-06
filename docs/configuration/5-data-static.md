<!--
title: "Data and static files"
description: "Data files and static files options."
date: 2021-05-07
updated: 2026-10-05
-->
# Data and static files

## Data

Where data files are stored and what extensions are handled.

Supported formats: YAML, JSON, XML and CSV.

### data.dir

Data source directory (`data` by default).

```yaml
data:
  dir: data
```

### data.ext

Array of files extensions.

```yaml
data:
  ext: [yaml, yml, json, xml, csv]
```

### data.load

Enables `site.data` collection (`true` by default).

```yaml
data:
  load: true
```

---

## Static

Management of static files are copied (PDF, fonts, etc.).

:::important
You should put your assets files, used by [`asset()`](../assets/index.md#asset), in the [`assets` directory](6-assets.md#assets-dir) to avoid unnecessary files copy.
:::

### static.dir

Static files source directory (`static` by default).

```yaml
static:
  dir: static
```

### static.target

Directory where static files are copied (root by default).

```yaml
static:
  target: ''
```

### static.exclude

List of excluded files. Accepts globs, strings and regexes.

```yaml
static:
  exclude: ['sass', 'scss', '*.scss', 'package*.json', 'node_modules']
```

:::tip
If you use [Bootstrap Icons](https://icons.getbootstrap.com) you can exclude the `node_modules` except `node_modules/bootstrap-icons` with a regular expression:

```yaml
exclude: ['sass', 'scss', '*.scss', 'package*.json', '#node_modules/(?!bootstrap-icons)#']
```

:::

### static.load

Enables `site.static` collection (`false` by default).

```yaml
static:
  load: false
```

### static.mounts

Allows to copy specific files or directories to a specific destination.

```yaml
static:
  mounts: []
```

### static example

```yaml
static:
  dir: docs
  target: docs
  exclude: ['sass', '*.scss', '/\.bck$/']
  load: true
  mounts:
    - source/path/file.ext: dest/path/file.ext
    - node_modules/bootstrap-icons/font/fonts: fonts
```
