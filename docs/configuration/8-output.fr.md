<!--
title: "Sortie"
description: "Dossier de sortie, formats et post-traitement."
date: 2026-03-27
updated: 2026-10-05
path: documentation/configuration/sortie
-->
# Sortie

Définit où et dans quel format les pages sont rendues.

## output.dir

Répertoire où les fichiers de pages rendues sont enregistrés (`_site` par défaut).

```yaml
output:
  dir: _site
```

## output.formats

Liste de définition des formats de sortie, utilisés pour rendre les pages (par ex. HTML, Atom, RSS, JSON, XML, etc.).

```yaml
output:
  formats:
    - name: <name>            # nom du format, par ex. `html` (requis)
      mediatype: <media type> # type MIME, ex. `text/html` (facultatif)
      subpath: <sub path>     # sous-chemin, ex. `amp` dans `path/amp/index.html` (facultatif)
      filename: <file name>   # nom du fichier, ex. `index` dans `path/index.html` (facultatif)
      extension: <extension>  # extension du fichier, ex. `html` dans `path/index.html` (requis)
      exclude: [<variable>]   # n’applique pas ce format aux pages identifiées par les variables listées, ex. `[redirect, paginated]` (facultatif)
```

Ces formats sont utilisés dans la configuration [`output.pagetypeformats`](#output-pagetypeformats) et dans la variable de page [`output`](../content/2-front-matter.fr.md#output).

### Formats par défaut

Cecil fournit quelques [formats par défaut](https://github.com/Cecilapp/Cecil/blob/main/config/base.php#L81-L162), qui peuvent être surchargés dans le fichier de configuration : `html` (par défaut), `atom`, `rss`, `json`, `xml`, `txt`, `amp`, `js`, `webmanifest`, `xsl`, `jsonfeed`, `iframe`, `oembed`.

## output.pagetypeformats

Il n’est pas nécessaire de définir la variable `output` pour chaque page, car Cecil applique automatiquement les formats définis pour chaque type de page (`homepage`, `page`, `section`, `vocabulary` et `term`).

```yaml
output:
  pagetypeformats:
    page: [<format>]
    homepage: [<format>]
    section: [<format>]
    vocabulary: [<format>]
    term: [<format>]
```

Plusieurs formats peuvent être définis pour un même type de page. Par exemple, le type de page `section` peut être rendu automatiquement en HTML et Atom :

```yaml
output:
  pagetypeformats:
    section: [html, atom]
```

:::info
Pour rendre une page, [Cecil recherche un template](../templates/1-lookup-rules.fr.md#regles-de-recherche) nommé `<layout>.<format>.twig` (ex. `page.html.twig`).
:::

## exemple de output

```yaml
output:
  dir: _site
  formats:
    - name: html
      mediatype: text/html
      filename: index
      extension: html
    - name: atom
      mediatype: application/xml
      filename: atom
      extension: xml
      exclude: [redirect, paginated]
  pagetypeformats:
    page: [html]
    homepage: [html, atom]
    section: [html, atom]
    vocabulary: [html]
    term: [html, atom]
```

## Post-process

Vous pouvez étendre les capacités de Cecil avec un [post-processeur de sortie](../developers/1-extend.fr.md#post-processeur-de-rendu) pour modifier les fichiers de sortie après leur génération.

---
