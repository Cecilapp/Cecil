<!--
title: "Composants"
description: "Créez des composants de template réutilisables."
date: 2026-05-26
updated: 2026-10-05
path: documentation/templates/composants
-->
# Composants

Cecil fournit une logique de composants pour vous donner le pouvoir de créer des "unités" de templates réutilisables.

:::info
La fonctionnalité des composants est fournie par l'[_extension de composants Twig_](https://github.com/giorgiopogliani/twig-components) créée par Giorgio Pogliani.
:::

## Syntaxe des composants

Les composants ne sont que des templates Twig stockés dans le sous-répertoire `components/` et peuvent être utilisés n'importe où dans vos templates :

```twig
{# /components/button.twig #}
<button {{ attributes.merge({class: 'rounded px-4'}) }}>
    {{ slot }}
</button>
```

> La variable slot correspond à tout contenu que vous ajouterez entre la balise d'ouverture et la balise de fermeture.

Pour accéder à un composant vous devez utiliser la balise dédiée `x` suivie de `:` et du nom de fichier de votre composant sans extension :

```twig
{# /index.twig #}
{% x:button with {class: 'text-white'} %}
    <strong>Click me</strong>
{% endx %}
```

Il rendra :

```twig
<button class="text-white rounded px-4">
    <strong>Click me</strong>
</button>
```
