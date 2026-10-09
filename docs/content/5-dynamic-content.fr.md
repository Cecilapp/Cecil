<!--
title: "Contenu dynamique"
description: "Utilisez des variables et expressions Twig dans le contenu des pages."
date: 2026-03-27
updated: 2026-10-09
path: documentation/contenu/contenu-dynamique
-->
# Contenu dynamique

Par défaut, le corps d’une page est statique : la syntaxe Twig écrite dans un fichier Markdown est affichée telle quelle.

Vous pouvez créer du contenu dynamique dans une page en utilisant la fonction Twig [`template_from_string`](https://twig.symfony.com/doc/3.x/functions/template_from_string.html), qui interprète le contenu de la page comme un template Twig.

```twig
{{ include(template_from_string(page.content, "contenu dynamique pour la page " ~ page.id)) }}
```

Avec cette approche, vous pouvez utiliser n’importe quelle variable de page dans le _body_ de la page.

```twig
---
var: 'value'
---
La valeur de `var` est {{ page.var }}.
```

## Activer le contenu dynamique

La méthode recommandée consiste à créer un layout dédié qui surcharge le bloc `content`, et à ne l’utiliser que pour les pages qui en ont besoin.

```twig
{# layouts/dynamic.html.twig #}
{% extends '_default/page.html.twig' %}

{% block content %}
{{ include(template_from_string(page.content, "contenu dynamique pour la page " ~ page.id)) }}
{% endblock content %}
```

Puis définissez la variable [`layout`](2-front-matter.fr.md#variables-predefinies) dans le front matter de la page :

```yaml
---
title: "Ma page dynamique"
layout: dynamic
---
```

:::tip
Le second argument de `template_from_string` est le nom du template : il est affiché dans les messages d’erreur, inclure `page.id` permet donc de retrouver la page en erreur.
:::

## Variables disponibles

Le contenu de la page est rendu avec le même contexte que le layout, vous pouvez donc utiliser :

- les variables de page : `page.title`, `page.date`, `page.<variable personnalisée>`, etc.
- les variables du site : `site.title`, `site.pages`, [`site.data`](../templates/2-variables.fr.md#site-data), etc.
- toutes les [fonctions](../templates/reference/1-functions.fr.md), [filtres](../templates/reference/3-filters.fr.md) et [tris](../templates/reference/2-sorts.fr.md) disponibles dans les templates ;
- les tags Twig : `{% set %}`, `{% if %}`, `{% for %}`, `{% include %}`, etc.

## Fonctionnement

Le corps Markdown est **d’abord converti en HTML**, puis le résultat (`page.content`) est rendu par Twig. Cet ordre a quelques conséquences :

- Markdown échappe les caractères `<` et `>`, les opérateurs de comparaison (`>`, `<=`) et les fonctions fléchées (`=>`) ne peuvent donc pas être utilisés dans le corps : le build échoue avec une erreur `Unexpected character "&"`.
- Une expression Twig utilisée dans un attribut HTML (ex. : `href="{{ url(post) }}"`) est encodée (URL) par le convertisseur Markdown et ne sera pas évaluée.
- Une expression Twig seule sur sa ligne est encapsulée dans un élément `<p>`.
- La syntaxe Twig écrite dans un bloc ou une portion de code **est** également évaluée.

:::tip
Gardez le corps simple (variables, filtres, conditions courtes) et déplacez le balisage et la logique complexes dans un [template partiel](#exemple-inclure-un-template-partiel) ou une [macro](#exemple-shortcodes-avec-des-macros).
:::

Pour afficher la syntaxe Twig telle quelle, encadrez-la avec le tag `verbatim` :

```twig
{% verbatim %}`{{ page.title }}`{% endverbatim %}
```

Pour éviter un élément `<p>` superflu autour d’un bloc HTML, encadrez l’expression dans un élément HTML, séparé par des retours à la ligne :

```twig
<div>
{{ include('partials/latest-posts.html.twig') }}
</div>
```

## Exemple : variables, données et template partiel {#exemple-inclure-un-template-partiel}

Cet exemple montre une page affichant des variables du site et de la page, des données issues d’un [fichier de données](../templates/2-variables.fr.md#site-data), ainsi que les derniers articles du blog via un template partiel réutilisable.

Fichier de données :

```yaml
# data/team.yml
- name: Alice
  role: Développeuse
- name: Bob
  role: Designer
```

Template partiel, qui reçoit `section` et `limit` en paramètres :

```twig
{# layouts/partials/latest-posts.html.twig #}
{% set posts = site.pages.showable|filter_by('section', section|default('blog'))|sort_by_date|slice(0, limit|default(5)) %}
{% if posts|length > 0 %}
<ul class="latest-posts">
  {% for post in posts %}
  <li><a href="{{ url(post) }}">{{ post.title }}</a> <time datetime="{{ post.date|date('Y-m-d') }}">{{ post.date|format_date('long') }}</time></li>
  {% endfor %}
</ul>
{% else %}
<p>Aucun article pour le moment.</p>
{% endif %}
```

Page :

```markdown
---
title: À propos
layout: dynamic
---
Bienvenue sur **{{ site.title }}** ! Cette page a été publiée le {{ page.date|format_date('long') }}.

Notre équipe compte {{ site.data.team|length }} membres : {{ site.data.team|column('name')|join(', ') }}.

## Derniers articles

<div>
{{ include('partials/latest-posts.html.twig', {section: 'blog', limit: 3}) }}
</div>
```

Résultat :

```html
<p>Bienvenue sur <strong>Mon site</strong> ! Cette page a été publiée le 9 octobre 2026.</p>
<p>Notre équipe compte 2 membres : Alice, Bob.</p>
<h2 id="derniers-articles">Derniers articles</h2>
<div>
<ul class="latest-posts">
  <li><a href="/blog/post-3/">Post 3</a> <time datetime="2026-03-01">1 mars 2026</time></li>
  <li><a href="/blog/post-2/">Post 2</a> <time datetime="2026-02-01">1 février 2026</time></li>
  <li><a href="/blog/post-1/">Post 1</a> <time datetime="2026-01-01">1 janvier 2026</time></li>
</ul>
</div>
```

:::info
L’opérateur `>` et l’attribut `href="{{ … }}"` fonctionnent ici car ils sont écrits dans le template partiel, et non dans le corps Markdown.
:::

## Exemple : shortcodes avec des macros {#exemple-shortcodes-avec-des-macros}

Les [macros Twig](https://twig.symfony.com/doc/3.x/tags/macro.html) peuvent être utilisées comme _shortcodes_ pour insérer des fragments HTML riches dans le corps d’une page.

Créez les macros :

```twig
{# layouts/macros/shortcodes.html.twig #}
{% macro youtube(id, title = 'Vidéo YouTube') %}
<iframe src="https://www.youtube-nocookie.com/embed/{{ id }}" title="{{ title }}" width="560" height="315" loading="lazy" allowfullscreen></iframe>
{% endmacro %}

{% macro alert(message, type = 'info') %}
<div class="alert alert-{{ type }}" role="alert">{{ message }}</div>
{% endmacro %}
```

Créez un layout qui importe les macros avant de rendre le contenu :

```twig
{# layouts/shortcodes.html.twig #}
{% extends '_default/page.html.twig' %}

{% block content %}
{% set imports = "{% import 'macros/shortcodes.html.twig' as sc %}" %}
{{ include(template_from_string(imports ~ page.content, "contenu dynamique pour la page " ~ page.id)) }}
{% endblock content %}
```

Utilisez les shortcodes dans une page :

```markdown
---
title: "Vidéo de démo"
layout: shortcodes
---
<div>
  {{ sc.alert('Cette vidéo est en anglais.', 'warning') }}
</div>

## Démo

<div>
  {{ sc.youtube('NaB8JBfE7DY', 'Démo de Cecil') }}
</div>
```

Résultat :

```html
<div>
  <div class="alert alert-warning" role="alert">Cette vidéo est en anglais.</div>
</div>
<h2 id="demo">Démo</h2>
<div>
  <iframe src="https://www.youtube-nocookie.com/embed/NaB8JBfE7DY" title="Démo de Cecil" width="560" height="315" loading="lazy" allowfullscreen></iframe>
</div>
```

:::tip
Pour réutiliser des éléments d’interface entre templates et pages, voir aussi les [composants](../templates/4-components.fr.md).
:::
