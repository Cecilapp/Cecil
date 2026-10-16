<!--
title: "Components"
description: "Create reusable template components."
date: 2021-05-07
updated: 2026-10-16
-->
# Components

Cecil provides components to help you build reusable template "units".

:::info
The components feature is provided by the [_Twig components extension_](https://github.com/giorgiopogliani/twig-components) created by Giorgio Pogliani.
:::

## Components syntax

Components are Twig templates stored in the `components/` subdirectory of `layouts/` (e.g.: `layouts/components/button.twig`), and can be used anywhere in your templates:

```twig
{# layouts/components/button.twig #}
<button {{ attributes.merge({class: 'rounded px-4'}) }}>
    {{ slot }}
</button>
```

> The `slot` variable contains any content you add between the opening and closing tags.

To use a component, use the dedicated tag `x` followed by `:` and the file name of your component without extension:

```twig
{# layouts/index.html.twig #}
{% x:button with {class: 'text-white'} %}
    <strong>Click me</strong>
{% endx %}
```

It will render:

```html
<button class="text-white rounded px-4">
    <strong>Click me</strong>
</button>
```

### HTML syntax

The same component can be used with an HTML-like syntax:

```twig
<x-button class="text-white">
    <strong>Click me</strong>
</x-button>
```

To evaluate an attribute value as a Twig expression, prefix its name with `:` (e.g.: `<x-button :label="page.title">`).

### Subfolders

Components stored in subfolders are reached with the _dot notation_:

```twig
{# layouts/components/button/primary.twig #}
{% x:button.primary %}Click me{% endx %}
{# or #}
<x-button.primary>Click me</x-button.primary>
```

### Named slots

A component can expose several named slots:

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

The components directory and file extension can be changed with the [`layouts.components`](../configuration/7-layouts.md#layouts-components) options (`dir`, `components` by default, and `ext`, `twig` by default).
