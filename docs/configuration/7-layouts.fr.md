<!--
title: "Layouts"
description: "Dossier des templates, échappement, images, traductions et composants."
date: 2026-03-27
updated: 2026-10-05
-->
# Layouts

Options des templates.

## layouts.dir

Répertoire source des templates (`layouts` par défaut).

```yaml
layouts:
  dir: layouts
```

## layouts.autoescape

Surcharge l’option Twig `autoescape` (`false` par défaut).

Si la valeur est `null`, Cecil applique une stratégie basée sur l’extension du nom de template :

- `*.js.twig` -> `js`
- `*.css.twig` -> `css`
- `*.html.twig` et `*.twig` -> `html`
- toute autre extension -> `false`

```yaml
layouts:
  autoescape: false  # désactive l’échappement automatique (par défaut)
  #autoescape: null  # utilise la stratégie automatique Cecil selon l’extension du template
  #autoescape: html
  #autoescape: js
```

## layouts.images

Options de gestion des images.

```yaml
layouts:
  images:
    formats: []       # utilisé par la fonction `html` : ajoute des formats d’image alternatifs comme `source` (ex. `[avif, webp]`, tableau vide par défaut)
    responsive: false # utilisé par la fonction `html` : ajoute des images responsives ('width' ou 'density', `false` par défaut)
    placeholder: ''   # utilisé par la fonction `html` : remplit l’arrière-plan de l’image avant son chargement (`color` ou `lqip`, désactivé par défaut)
    dark_suffix: ''   # suffixe de l’image variante sombre (ex. `.dark`), désactivé par défaut
    mobile_suffix: '' # suffixe de l’image variante mobile (ex. `.mobile`), désactivé par défaut
    mobile_media_query: '(max-width: 767px)' # media query de la `<source>` de la variante mobile
```

## layouts.translations

Options de gestion des traductions.

```yaml
layouts:
  translations:
    dir: translations # répertoire source des traductions (`translations` par défaut)
    formats:          # formats de traduction pris en charge
      yaml:
        loader: Symfony\Component\Translation\Loader\YamlFileLoader
        ext: [yml, yaml]
      mo:
        loader: Symfony\Component\Translation\Loader\MoFileLoader
        ext: [mo]
```

Chaque format de traduction définit :

- `loader` : classe du chargeur de traduction Symfony
- `ext` : une ou plusieurs extensions de fichier associées à ce format

## layouts.components

Options des [composants de template](../templates/4-components.fr.md).

```yaml
layouts:
  components:
    dir: components # répertoire source des composants (`components` par défaut)
    ext: twig       # extension des fichiers de composants (`twig` par défaut)
```

## layouts.sections

Associe une section aux layouts d’une autre section : le nom associé est utilisé à la place de `<section>` par les [règles de recherche](../templates/1-lookup-rules.fr.md#regles-de-recherche) de la section et de ses pages.

```yaml
layouts:
  sections:
    news: blog # la section « news » est rendue avec `blog/list.html.twig` et ses pages avec `blog/page.html.twig`
```

:::tip
Une [sous-section](../content/1-pages.fr.md#sous-section) se replie déjà sur les layouts de ses sections parentes : aucune association n’est nécessaire pour cela.
:::

---
