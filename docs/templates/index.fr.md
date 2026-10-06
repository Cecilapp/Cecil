<!--
title: "Templates"
description: "Travaillez avec les layouts, templates et composants Twig."
date: 2026-05-26
updated: 2026-10-05
weight: 3
sortby: weight
alias: documentation/layouts
-->
# Templates

Cecil est alimenté par le moteur de templates [Twig](https://twig.symfony.com), veuillez donc vous référer à la **[documentation officielle](https://twig.symfony.com/doc/templates.html)** pour savoir comment l'utiliser.

## Exemple

```twig
{# template d'exemple #}
<h1>{{ page.title }} - {{ site.title }}</h1>
<span>{{ page.date|date('j M Y') }}</span>
<p>{{ page.content }}</p>
<ul>
{% for tag in page.tags %}
  <li>{{ tag }}</li>
{% endfor %}
</ul>
```

- `{# #}` : ajoute des commentaires
- `{{ }}` : affiche le contenu des variables ou des expressions
- `{% %}` : exécute des instructions, comme une boucle (`for`), une condition (`if`), etc.
- `|filter()` : filtre ou formate le contenu
