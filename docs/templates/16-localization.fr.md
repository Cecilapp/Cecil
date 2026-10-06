<!--
title: "Localisation"
description: "Traduisez les textes et localisez les dates dans les templates."
date: 2026-05-26
updated: 2026-10-05
path: documentation/templates/localisation
-->
# Localisation

Cecil prend en charge [traduction du texte](#traduction-de-texte) et [localisation de la date](#localisation-de-la-date).

## Traduction de texte

Utilise le `trans` _tag_ ou _filter_ pour traduire des textes dans des templates.

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

Inclure des variables :

```twig
{% trans with {'%name%': 'Arnaud'} %}Hello %name%!{% endtrans %}
```

```twig
{{ message|trans({'%name%': 'Arnaud'}) }}
```

Forcer les paramètres régionaux :

```twig
{% trans into 'fr_FR' %}Hello World!{% endtrans %}
```

Pluraliser :

```twig
{% trans with {'%count%': 42}%}{0}I don't have apples|{1}I have one apple|]1,Inf[I have %count% apples{% endtrans %}
```

## Fichiers de traduction

Les fichiers de traduction doivent être nommés `messages.<locale>.<extension>` et stockés dans le répertoire [`translations`](../configuration/28-layouts.fr.md).
Les extensions prises en charge sont définies pour chaque format de traduction dans [`layouts.translations.formats`](../configuration/28-layouts.fr.md#layouts-translations).

Le code locale (ex. : `fr_FR`) d'une langue est défini dans les entrées [`languages`](../configuration/23-languages.fr.md#languages) de la configuration.

_Exemple:_

```plaintext
<mywebsite>
└─ translations
   ├─ messages.fr_FR.mo   <- Machine Object format
   └─ messages.fr_FR.yaml <- Yaml format
```

:::info
Vous pouvez facilement extraire les traductions de vos templates avec la commande suivante :

```bash
php cecil.phar util:translations:extract --locale=<code> --show
```

Utilisez `--save` à la place (ou en plus) de `--show` pour enregistrer les traductions dans un fichier. L'option `--locale` est obligatoire. Le format de sortie par défaut est `yaml` (utilisez `--format=po` pour le format gettext PO).

:::

:::tip
[_Poedit_](https://poedit.net) est un éditeur de traduction simple et multiplateforme pour gettext (PO), et [_Poedit Pro_](https://poedit.net/pro) prend en charge l'extraction de chaînes de traduction à partir de templates prêts à l'emploi.
:::

:::important
Faites attention au [cache](17-cache.fr.md) lorsque vous mettez à jour les fichiers de traduction.

Le cache peut être vidé avec la commande suivante :

```bash
php cecil.phar cache:clear:translations`
```

:::

## Localisation de la date

Utilise le filtre Twig [`format_date`](https://twig.symfony.com/doc/3.x/filters/format_date.html) pour localiser une date dans les templates.

```twig
{{ page.date|format_date('long') }}
{# September 30, 2022 #}
```

Les valeurs prises en charge sont : `short`, `medium`, `long` et `full`.

:::important
Si vous souhaitez utiliser le filtre `format_date` **avec des paramètres régionaux autres que "en"**, vous devez [installer l'extension PHP internationale](https://php.net/intl.setup).
:::
