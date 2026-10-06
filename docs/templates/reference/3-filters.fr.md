<!--
title: "Filtres"
description: "filter_by, markdown_to_html, toc, slugify, excerpt, highlight, preg_*, etc."
date: 2026-05-26
updated: 2026-10-05
path: documentation/templates/reference/filtres
-->
# Filtres

Les variables peuvent être modifiées par [filtres](https://twig.symfony.com/doc/filters/index.html). Les filtres sont séparés de la variable par un symbole de barre verticale (`|`). Plusieurs filtres peuvent être chaînés. La sortie d’un filtre est appliquée au suivant.

```twig
{{ page.title|truncate(25)|capitalize }}
```

## filter_by

Filtre une collection de pages par nom/valeur de variable.

```twig
{{ collection|filter_by(variable, value) }}
```

_Exemple:_

```twig
{{ pages|filter_by('section', 'blog') }}
```

## filter

Pour les cas plus complexes, vous devez utiliser [le `filter`](https://twig.symfony.com/doc/filters/filter.html) natif de Twig.

_Exemple:_

```twig
{% pages|filter(p => p.virtual == false and p.id not in ['page-1', 'page-2']) %}
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

_Exemples :_

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

Extrait uniquement les en-têtes correspondant au `selectors` donné (h2, h3, etc.), ou à ceux définis dans la configuration `pages.body.toc` s'ils ne sont pas spécifiés.
Le paramètre `format` définit le format de sortie : `html` ou `json`.
Le paramètre `url` est utilisé pour créer des liens vers des titres.

```twig
{{ markdown|toc(format, selectors, url) }}
```

_Exemples :_

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

_Exemple:_

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

_Exemple:_

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

_Exemple:_

```twig
{{ 'cecil_string with twig'|u.camel.title }}
```

> CecilStringAvecTwig

## singular

Le filtre `singular` transforme un nom donné au pluriel en sa version singulière.

```twig
{{ string|singular(locale)}}
```

_Exemple:_

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

Le filtre `plural` transforme un nom donné au singulier en sa version plurielle.

```twig
{{ string|plural(locale)}}
```

_Exemple:_

```twig
{# English (en) rules are used by default #}
{{ 'animal'|plural }}
```

> animaux

```twig
{{ 'animal'|plural('fr') }}
```

> animaux

## excerpt

Tronque une chaîne et ajoute un suffixe.

```twig
{{ string|excerpt(length, suffix) }}
```

| Options  | Descriptif                             | Tapez   | Par défaut |
| -------- | -------------------------------------- | ------- | ---------- |
| longueur | Tronque après ce nombre de caractères. | entier  | 450        |
| suffixe  | Ajoute des caractères.                 | chaîne  | `…`        |

_Exemples :_

```twig
{{ variable|excerpt }}
{{ variable|excerpt(250, '...') }}
```

## excerpt_html

Lit les caractères avant ou après la balise `<!-- excerpt -->` ou `<!-- break -->`.
Voir [Documentation de contenu](../../content/3-markdown.fr.md#extrait) pour plus de détails.

```twig
{{ string|excerpt_html({separator, capture}) }}
```

| Options    | Descriptif                                           | Tapez   | Par défaut      |
| ---------- | ---------------------------------------------------- | ------- | --------------- |
| séparateur | Chaîne à utiliser comme séparateur.                  | chaîne  | `excerpt|break` |
| capturer   | Pièce à capturer, `before` ou `after` le séparateur. | chaîne  | `before`        |

_Exemples :_

```twig
{{ variable|excerpt_html }}
{{ variable|excerpt_html({separator: 'excerpt|break', capture: 'before'}) }}
{{ variable|excerpt_html({capture: 'after'}) }}
```

## highlight

Met en surbrillance une chaîne de code avec [highlight.php](https://github.com/scrivo/highlight.php).

```twig
{{ code|highlight(language) }}
```

_Exemples :_

```twig
{{ '<?php echo $highlighted->value; ?>'|highlight('php') }}
```

## preg_split

Divise une chaîne en un tableau à l'aide d'une expression régulière.

```twig
{{ string|preg_split(pattern, limit) }}
```

_Exemple:_

```twig
{% set headers = page.content|preg_split('/<br[^>]*>/') %}
```

## preg_match_all

Effectue une correspondance d'expression régulière et renvoie le groupe pour toutes les correspondances.

```twig
{{ string|preg_match_all(pattern, group) }}
```

_Exemple:_

```twig
{% set tags = page.content|preg_match_all('/<[^>]+>(.*)<\/[^>]+>/') %}
```

## hex_to_rgb

Convertit une couleur hexadécimale en RVB.

```twig
{{ color|hex_to_rgb }}
```
