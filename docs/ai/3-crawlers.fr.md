<!--
title: "Robots d’IA"
description: "Contrôlez quels robots d’IA peuvent accéder à votre site avec robots.txt."
date: 2026-10-22
updated: 2026-10-22
path: documentation/ia/robots
-->
# Robots d’IA

Cecil génère un fichier `robots.txt` qui autorise tous les robots. Pour bloquer les robots d’IA (ou certains d’entre eux), ajoutez vos propres règles en créant une page avec le chemin `robots` :

_pages/robots.md_

```yaml
---
layout: robots
output: txt
---
User-agent: GPTBot
Disallow: /

User-agent: ClaudeBot
Disallow: /

User-agent: Google-Extended
Disallow: /
```

Le corps de la page est ajouté aux règles par défaut (voir les [pages par défaut](../configuration/4-pages.fr.md#pages-default)).

:::info
Les règles de `robots.txt` sont des directives : les robots respectueux les suivent, mais elles n’empêchent pas l’accès à votre contenu.
:::
