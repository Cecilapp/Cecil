<!--
title: "Données et fichiers statiques"
description: "Options des fichiers de données et des fichiers statiques."
date: 2026-03-27
updated: 2026-10-16
path: documentation/configuration/donnees-et-statiques
-->
# Données et fichiers statiques

Ces options définissent où sont stockés les fichiers de données et les fichiers statiques, et comment ils sont traités.

## Données

Emplacement des fichiers de données et extensions prises en charge.

Formats pris en charge : YAML, JSON, XML et CSV.

### data.dir

Répertoire source des données (`data` par défaut).

```yaml
data:
  dir: data
```

### data.ext

Liste des extensions de fichiers.

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

Gestion des fichiers statiques, copiés tels quels (PDF, polices, etc.).

:::important
Vous devriez placer les fichiers d’assets, utilisés par [`asset()`](../assets/index.fr.md#asset), dans le [répertoire `assets`](6-assets.fr.md#assets-dir) afin d’éviter des copies de fichiers inutiles.
:::

### static.dir

Répertoire source des fichiers statiques (`static` par défaut).

```yaml
static:
  dir: static
```

### static.target

Répertoire vers lequel les fichiers statiques sont copiés (racine par défaut).

```yaml
static:
  target: ''
```

### static.exclude

Liste des fichiers exclus. Accepte les globs, les chaînes et les expressions régulières.

```yaml
static:
  exclude: ['sass', 'scss', '*.scss', 'package*.json', 'node_modules']
```

:::tip
Si vous utilisez [Bootstrap Icons](https://icons.getbootstrap.com), vous pouvez exclure `node_modules` sauf `node_modules/bootstrap-icons` avec une expression régulière :

```yaml
static:
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

Copie des fichiers ou répertoires spécifiques vers une destination spécifique : chaque entrée associe un chemin source, relatif au répertoire [`static.dir`](#static-dir), à un chemin de destination, relatif au répertoire de sortie.

```yaml
static:
  mounts:
    <source>: <destination>
```

### Exemple de configuration static

```yaml
static:
  dir: docs
  target: docs
  exclude: ['sass', '*.scss', '/\.bck$/']
  load: true
  mounts:
    source/path/file.ext: dest/path/file.ext
    vendor/icons/fonts: fonts
```

---
