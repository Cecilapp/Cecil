<!--
title: "Données et fichiers statiques"
description: "Options des fichiers de données et des fichiers statiques."
date: 2026-03-27
updated: 2026-10-10
path: documentation/configuration/donnees-et-statiques
-->
# Données et fichiers statiques

Ces options définissent où sont stockés les fichiers de données et les fichiers statiques, et comment ils sont traités.

## Données

Emplacement des fichiers de données et types d’extensions pris en charge.

Formats pris en charge : YAML, JSON, XML et CSV.

### data.dir

Répertoire source des données (`data` par défaut).

```yaml
data:
  dir: data
```

### data.ext

Tableau des extensions de fichiers.

```yaml
data:
  ext: [yaml, yml, json, xml, csv]
```

### data.load

Active la collection `site.data` (`true` par défaut).

```yaml
data:
  load: true
```

---

## Fichiers statiques

Gestion des fichiers statiques copiés (PDF, polices, etc.).

:::important
Vous devez placer les fichiers d’assets, utilisés par [`asset()`](../assets/index.fr.md#asset), dans le [`répertoire assets`](6-assets.fr.md#assets-dir) afin d’éviter des copies de fichiers inutiles.
:::

### static.dir

Répertoire source des fichiers statiques (`static` par défaut).

```yaml
static:
  dir: static
```

### static.target

Répertoire vers lequel les fichiers statiques sont copiés (`root` par défaut).

```yaml
static:
  target: ''
```

### static.exclude

Liste des fichiers exclus. Accepte les glob, les chaînes et les expressions régulières.

```yaml
static:
  exclude: ['sass', 'scss', '*.scss', 'package*.json', 'node_modules']
```

:::tip
Si vous utilisez [Bootstrap Icons](https://icons.getbootstrap.com), vous pouvez exclure `node_modules` sauf `node_modules/bootstrap-icons` avec une expression régulière :

```yaml
exclude: ['sass', 'scss', '*.scss', 'package*.json', '#node_modules/(?!bootstrap-icons)#']
```

:::

### static.load

Active la collection `site.static` (`false` par défaut).

```yaml
static:
  load: false
```

### static.mounts

Permet de copier des fichiers ou répertoires spécifiques vers une destination spécifique.

```yaml
static:
  mounts: []
```

### exemple de static

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
