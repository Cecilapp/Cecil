<!--
title: "Filtres"
description: "filter_by, markdown_to_html, toc, slugify, excerpt, highlight, preg_*, etc."
date: 2026-05-26
updated: 2026-10-18
path: documentation/templates/reference/filtres
-->
# Filtres

Les variables peuvent être modifiées par des [filtres](https://twig.symfony.com/doc/filters/index.html). Les filtres sont séparés de la variable par un symbole de barre verticale (`|`). Plusieurs filtres peuvent être chaînés. La sortie d’un filtre est appliquée au suivant.

```twig
{{ page.title|truncate(25)|capitalize }}
```

:::info
Voir aussi : les filtres dédiés aux assets (`resize`, `minify`, `fingerprint`, `to_css`, `inline`, `webp`, `lqip`, etc.) sont documentés dans la section [Assets](../../assets/index.fr.md).
:::

## filter_by

Filtre une collection de pages par nom/valeur de variable.

```twig
{{ collection|filter_by(variable, value) }}
```

Seules les pages standards (de type `page`, non virtuelles) sont renvoyées.

_Exemple :_

```twig
{{ pages|filter_by('section', 'blog') }}
```

## filter

Pour les cas plus complexes, il est préférable d’utiliser le filtre natif [`filter`](https://twig.symfony.com/doc/filters/filter.html) de Twig.

_Exemple :_

```twig
{% set filtered = pages|filter(p => p.virtual == false and p.id not in ['page-1', 'page-2']) %}
```

## markdown_to_html

Convertit une chaîne Markdown en HTML.

```twig
{{ markdown|markdown_to_html }}
```

```twig
{% apply markdown_to_html %}
{# Markdown here #}
{% endapply %}
```

_Exemples :_

```twig
{% set markdown = '**This is bold text**' %}
{{ markdown|markdown_to_html }}
```

```twig
{% apply markdown_to_html %}
**This is bold text**
{% endapply %}
```

## toc

Extrait uniquement les titres correspondant aux `selectors` donnés (h2, h3, etc.), ou à ceux définis dans la configuration `pages.body.toc` s’ils ne sont pas spécifiés.
Le paramètre `format` définit le format de sortie : `html` (par défaut) ou `json`.
Le paramètre `url` est utilisé pour créer les liens vers les titres.

```twig
{{ markdown|toc(format, selectors, url) }}
```

_Exemples :_

```twig
{{ page.body|toc }}
{{ page.body|toc('html') }}
{{ page.body|toc(selectors=['h2']) }}
{{ page.body|toc(url=url(page)) }}
```

## json_decode

Convertit une chaîne JSON en tableau.

```twig
{{ json|json_decode }}
```

_Exemple :_

```twig
{% set json = '{"foo": "bar"}' %}
{% set array = json|json_decode %}
{{ array.foo }}
```

## yaml_parse

Convertit une chaîne YAML en tableau.

```twig
{{ yaml|yaml_parse }}
```

_Exemple :_

```twig
{% set yaml = 'key: value' %}
{% set array = yaml|yaml_parse %}
{{ array.key }}
```

## slugify

Convertit une chaîne en slug.

```twig
{{ string|slugify }}
```

## u

Le filtre `u` enveloppe un texte dans un objet Unicode (une [instance Symfony UnicodeString](https://symfony.com/doc/current/components/string.html)) qui expose des méthodes pour « manipuler » la chaîne.

_Exemple :_

```twig
{{ 'cecil_string with twig'|u.camel.title }}
```

> CecilStringWithTwig

## singular

Le filtre `singular` transforme un nom donné au pluriel en sa version au singulier.

```twig
{{ string|singular(locale) }}
```

_Exemple :_

```twig
{# English (en) rules are used by default #}
{{ 'partitions'|singular }}
```

> partition

```twig
{{ 'partitions'|singular('fr') }}
```

> partition

## plural

Le filtre `plural` transforme un nom donné au singulier en sa version au pluriel.

```twig
{{ string|plural(locale) }}
```

_Exemple :_

```twig
{# English (en) rules are used by default #}
{{ 'animal'|plural }}
```

> animals

```twig
{{ 'animal'|plural('fr') }}
```

> animaux

## excerpt

Tronque une chaîne et ajoute un suffixe.

```twig
{{ string|excerpt(length, suffix) }}
```

| Option | Description                            | Type    | Par défaut                         |
| ------ | -------------------------------------- | ------- | ---------------------------------- |
| length | Tronque après ce nombre de caractères. | entier  | 450                                |
| suffix | Ajoute des caractères.                 | chaîne  | `' …'` (espace et points de suspension) |

_Exemples :_

```twig
{{ variable|excerpt }}
{{ variable|excerpt(250, '...') }}
```

## excerpt_html

Lit les caractères avant ou après la balise `<!-- excerpt -->` ou `<!-- break -->`.
Voir la [documentation du contenu](../../content/3-markdown.fr.md#extrait) pour plus de détails.

```twig
{{ string|excerpt_html({separator, capture}) }}
```

| Option    | Description                                           | Type    | Par défaut      |
| --------- | ----------------------------------------------------- | ------- | --------------- |
| separator | Chaîne à utiliser comme séparateur.                   | chaîne  | `excerpt|break` |
| capture   | Partie à capturer, `before` ou `after` le séparateur. | chaîne  | `before`        |

Les valeurs par défaut sont définies par la configuration `pages.body.excerpt`.

_Exemples :_

```twig
{{ variable|excerpt_html }}
{{ variable|excerpt_html({separator: 'excerpt|break', capture: 'before'}) }}
{{ variable|excerpt_html({capture: 'after'}) }}
```

## highlight

Met en surbrillance une chaîne de code.

```twig
{{ code|highlight(language, engine) }}
```

Le moteur de coloration (`highlightjs` ou `tempest`) est l’argument optionnel `engine` ou, par défaut, l’option [`layouts.highlight.engine`](../../configuration/7-layouts.fr.md#layouts-highlight) (`highlightjs` par défaut).

:::tip
Le filtre n’utilise pas l’option [`pages.body.highlight.engine`](../../configuration/4-pages.fr.md#pages-body-highlight), qui ne s’applique qu’au contenu Markdown.
:::

Si le langage n’est pas pris en charge par le moteur, le code est retourné échappé, sans coloration (et un avertissement est journalisé).

_Exemples :_

```twig
{{ '<?php echo $highlighted->value; ?>'|highlight('php') }}
{{ '<?php echo $highlighted->value; ?>'|highlight('php', 'tempest') }}
```

:::info
Chaque moteur utilise ses propres classes CSS (`hljs-*` pour `highlightjs`, `hl-*` pour `tempest`) : la feuille de style doit correspondre au moteur utilisé.
:::

## preg_split

Divise une chaîne en un tableau à l’aide d’une expression régulière (`limit` vaut `0`, c’est-à-dire sans limite, par défaut).

```twig
{{ string|preg_split(pattern, limit) }}
```

_Exemple :_

```twig
{% set headers = page.content|preg_split('/<br[^>]*>/') %}
```

## preg_match_all

Effectue une correspondance d’expression régulière et renvoie le groupe pour toutes les correspondances (`group` vaut `0`, c’est-à-dire la correspondance complète, par défaut).

```twig
{{ string|preg_match_all(pattern, group) }}
```

_Exemple :_

```twig
{% set tags = page.content|preg_match_all('/<[^>]+>(.*)<\/[^>]+>/') %}
```

## hex_to_rgb

Convertit une couleur hexadécimale en RVB : renvoie un tableau avec les clés `red`, `green` et `blue`.

```twig
{{ color|hex_to_rgb }}
```

_Exemple :_

```twig
{% set rgb = '#f00'|hex_to_rgb %}
rgb({{ rgb.red }}, {{ rgb.green }}, {{ rgb.blue }})
```

> rgb(255, 0, 0)

## splitline

Divise une chaîne en un tableau de lignes de `max` caractères maximum (`18` par défaut), sans couper les mots.

```twig
{{ string|splitline(max) }}
```

_Exemple :_

```twig
{% for line in 'The quick brown fox jumps'|splitline(10) %}
  {{ line }}<br>
{% endfor %}
```

> The quick<br>brown fox<br>jumps

## iterable

Convertit une variable en itérable (tableau) : un tableau est renvoyé tel quel, un objet itérable est converti en tableau, et toute autre valeur est placée dans un tableau.

```twig
{{ variable|iterable }}
```

_Exemple :_

```twig
{% for author in page.authors|iterable %}
  {{ author }}
{% endfor %}
```

## unique

Supprime les valeurs en double d’un tableau de chaînes (sans tenir compte de la casse : la première occurrence est conservée).

```twig
{{ array|unique }}
```

_Exemple :_

```twig
{{ ['PHP', 'php', 'Twig']|unique|join(', ') }}
```

> PHP, Twig

## duration_to_iso8601

Convertit une durée en secondes en [durée ISO 8601](https://fr.wikipedia.org/wiki/ISO_8601#Durée) (ex. : pour la `duration` d’une vidéo dans les données structurées).

```twig
{{ seconds|duration_to_iso8601 }}
```

_Exemple :_

```twig
{{ 125|duration_to_iso8601 }}
```

> PT00H00M125S
