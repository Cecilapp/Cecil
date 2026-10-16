<!--
title: "Localisation"
description: "Traduisez les textes et localisez les dates dans les templates."
date: 2026-05-26
updated: 2026-10-16
path: documentation/templates/localisation
-->
# Localisation

Cecil prend en charge la [traduction de texte](#traduction-de-texte) et la [localisation des dates](#localisation-de-la-date).

## Traduction de texte

Utilisez la _balise_ ou le _filtre_ `trans` pour traduire des textes dans les templates.

```twig
{% trans with variables into locale %}{% endtrans %}
```

```twig
{{ message|trans(variables = []) }}
```

### Exemples

```twig
{% trans %}Hello World!{% endtrans %}
```

```twig
{{ message|trans }}
```

Inclure des variables :

```twig
{% trans with {'%name%': 'Arnaud'} %}Hello %name%!{% endtrans %}
```

```twig
{{ message|trans({'%name%': 'Arnaud'}) }}
```

Forcer la locale :

```twig
{% trans into 'fr_FR' %}Hello World!{% endtrans %}
```

Pluraliser :

```twig
{% trans with {'%count%': 42} %}{0}I don't have apples|{1}I have one apple|]1,Inf[I have %count% apples{% endtrans %}
```

## Fichiers de traduction

Les fichiers de traduction doivent être nommés `messages.<locale>.<extension>` et stockés dans le répertoire [`translations`](../configuration/7-layouts.fr.md#layouts-translations).
Les extensions prises en charge sont définies pour chaque format de traduction dans [`layouts.translations.formats`](../configuration/7-layouts.fr.md#layouts-translations).

Le code de locale (ex. : `fr_FR`) d’une langue est défini dans les entrées [`languages`](../configuration/2-languages.fr.md#languages) de la configuration. Un fichier nommé avec le seul code de langue (ex. : `messages.fr.yaml`) est également chargé, et les traductions des [thèmes](7-extend.fr.md#theme) sont fusionnées avec celles du site.

_Exemple :_

```plaintext
<mon-site>
└─ translations
   ├─ messages.fr_FR.mo   <- Machine Object format
   └─ messages.fr_FR.yaml <- YAML format
```

:::info
Vous pouvez facilement extraire les traductions de vos templates avec la commande suivante :

```bash
php cecil.phar util:translations:extract --locale=<code> --show
```

Utilisez `--save` à la place (ou en plus) de `--show` pour enregistrer les traductions dans un fichier. L’option `--locale` est obligatoire. Le format de sortie par défaut est `yaml` (utilisez `--format=po` pour le format gettext PO).

:::

:::tip
[_Poedit_](https://poedit.net) est un éditeur de traduction simple et multiplateforme pour gettext (PO), et [_Poedit Pro_](https://poedit.net/pro) prend en charge nativement l’extraction des chaînes de traduction depuis les templates.
:::

:::important
Les traductions sont mises en [cache](6-cache.fr.md) : le cache est rafraîchi lorsqu’un fichier de traduction est modifié ou supprimé, mais pas lorsqu’un nouveau fichier est ajouté.

Le cache peut être vidé avec la commande suivante :

```bash
php cecil.phar cache:clear:translations
```

:::

## Localisation de la date

Utilisez le filtre Twig [`format_date`](https://twig.symfony.com/doc/3.x/filters/format_date.html) pour localiser une date dans les templates.

```twig
{{ page.date|format_date('long') }}
{# September 30, 2022 #}
```

Les valeurs prises en charge sont : `none`, `short`, `medium`, `long` et `full`.

Les filtres [`format_datetime`](https://twig.symfony.com/doc/3.x/filters/format_datetime.html) et [`format_time`](https://twig.symfony.com/doc/3.x/filters/format_time.html) sont également disponibles.

:::important
Si vous souhaitez utiliser ces filtres **avec d’autres locales que « en »**, vous devez [installer l’extension PHP intl](https://php.net/intl.setup).
:::
