<!--
title: "Components"
description: "Create reusable template components."
date: 2021-05-07
updated: 2026-10-05
-->
# Components

Cecil provides a components logic to give you the power making reusable template "units".

:::info
The components feature is provided by the [_Twig components extension_](https://github.com/giorgiopogliani/twig-components) created by Giorgio Pogliani.
:::

## Components syntax

Components are just Twig templates stored in the `components/` subdirectory and can be used anywhere in your templates:

```twig
{# /components/button.twig #}
<button {{ attributes.merge({class: 'rounded px-4'}) }}>
    {{ slot }}
</button>
```

> The slot variable is any content you will add between the opening and the close tag.

To reach a component you need to use the dedicated tag `x` followed by `:` and the filename of your component without extension:

```twig
{# /index.twig #}
{% x:button with {class: 'text-white'} %}
    <strong>Click me</strong>
{% endx %}
```

It will render:

```twig
<button class="text-white rounded px-4">
    <strong>Click me</strong>
</button>
```
