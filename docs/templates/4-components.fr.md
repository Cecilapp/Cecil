<!--
title: "Composants"
description: "Créez des composants de template réutilisables."
date: 2026-05-26
updated: 2026-10-16
path: documentation/templates/composants
-->
# Composants

Cecil fournit des composants pour vous aider à créer des « unités » de templates réutilisables.

:::info
La fonctionnalité des composants est fournie par l’[_extension de composants Twig_](https://github.com/giorgiopogliani/twig-components) créée par Giorgio Pogliani.
:::

## Syntaxe des composants

Les composants sont des templates Twig stockés dans le sous-répertoire `components/` de `layouts/` (ex. : `layouts/components/button.twig`), et peuvent être utilisés n’importe où dans vos templates :

```twig
{# layouts/components/button.twig #}
<button {{ attributes.merge({class: 'rounded px-4'}) }}>
    {{ slot }}
</button>
```

> La variable `slot` contient tout contenu ajouté entre la balise d’ouverture et la balise de fermeture.

Pour utiliser un composant, utilisez la balise dédiée `x` suivie de `:` et du nom de fichier du composant sans extension :

```twig
{# layouts/index.html.twig #}
{% x:button with {class: 'text-white'} %}
    <strong>Click me</strong>
{% endx %}
```

Ce qui produit :

```html
<button class="text-white rounded px-4">
    <strong>Click me</strong>
</button>
```

### Syntaxe HTML

Le même composant peut être utilisé avec une syntaxe de type HTML :

```twig
<x-button class="text-white">
    <strong>Click me</strong>
</x-button>
```

Pour évaluer la valeur d’un attribut comme une expression Twig, préfixez son nom par `:` (ex. : `<x-button :label="page.title">`).

### Sous-dossiers

Les composants stockés dans des sous-dossiers sont appelés avec la _notation pointée_ :

```twig
{# layouts/components/button/primary.twig #}
{% x:button.primary %}Click me{% endx %}
{# or #}
<x-button.primary>Click me</x-button.primary>
```

### Slots nommés

Un composant peut exposer plusieurs slots nommés :

```twig
{# layouts/components/card.twig #}
<div {{ attributes.class('card') }}>
    <h2 {{ title.attributes.class('card-title') }}>{{ title }}</h2>
    <div>{{ body }}</div>
</div>
```

```twig
{% x:card %}
    {% slot:title with {class: 'text-2xl'} %}Title{% endslot %}
    {% slot:body %}Body{% endslot %}
{% endx %}
{# or #}
<x-card>
    <x-slot name="title" class="text-2xl">Title</x-slot>
    <x-slot name="body">Body</x-slot>
</x-card>
```

## Configuration

Le répertoire et l’extension des fichiers de composants peuvent être modifiés avec les options [`layouts.components`](../configuration/7-layouts.fr.md#layouts-components) (`dir`, `components` par défaut, et `ext`, `twig` par défaut).
