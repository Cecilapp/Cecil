<!--
title: "Contenu dynamique"
description: "Utilisez des variables et expressions Twig dans le contenu des pages."
date: 2026-03-27
updated: 2026-10-03
path: documentation/contenu/contenu-dynamique
-->
# Contenu dynamique

Vous pouvez créer du contenu dynamique dans une page en utilisant la fonction Twig [`template_from_string`](https://twig.symfony.com/doc/3.x/functions/template_from_string.html).

```twig
{{ include(template_from_string(page.content, "contenu dynamique pour la page " ~ page.id)) }}
```

Avec cette approche, vous pouvez utiliser n’importe quelle variable de page dans le _body_ de la page.

```twig
--
var: 'value'
---
La valeur de `var` est {{ page.var }}.
```
