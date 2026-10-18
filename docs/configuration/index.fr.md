<!--
title: "Configuration"
description: "Configurez votre site avec le fichier cecil.yml."
date: 2026-03-27
updated: 2026-10-18
weight: 5
sortby: weight
-->
# Configuration

La configuration du site web est définie dans un fichier [YAML](https://en.wikipedia.org/wiki/YAML) nommé `cecil.yml` ou `config.yml`, stocké à la racine :

```plaintext
<mywebsite>
└─ cecil.yml
```

Cecil propose de nombreuses options de configuration, mais ses [valeurs par défaut](https://github.com/Cecilapp/Cecil/blob/main/config/default.php) sont souvent suffisantes. Un nouveau site ne nécessite que ces paramètres :

```yaml
title: "My new Cecil site"
baseurl: https://mywebsite.com/
description: "Site description"
```

Les pages suivantes décrivent les options de configuration prises en charge par Cecil.

Certaines options propres à une commande sont documentées avec celle-ci : `editor` ([`new:page`](../commands/2-new-page.fr.md)) et `doctor.*` ([`doctor`](../commands/5-doctor.fr.md)).

## Priorité

La configuration est construite à partir de plusieurs sources, chacune surchargeant les précédentes :

1. [configuration par défaut](https://github.com/Cecilapp/Cecil/blob/main/config/default.php) de Cecil
2. configuration des [thèmes](../templates/7-extend.fr.md#theme) (le premier thème surcharge les suivants)
3. configuration du site (`cecil.yml`)
4. [variables d’environnement](11-override.fr.md) et options de ligne de commande

Les tableaux associatifs (ex. `pages.body.images` ou `pages.default`) sont fusionnés clé par clé : il suffit de définir les clés à modifier.

Un tableau associatif peut être activé ou désactivé avec un booléen, sans perdre ses options :

```yaml
cache: false # équivaut à `cache: {enabled: false}`
```

## Listes

Lorsqu’une option est une liste de valeurs (ex. [`pages.ext`](4-pages.fr.md), [`output.pagetypeformats`](8-output.fr.md#output-pagetypeformats) ou [`assets.images.responsive.widths`](6-assets.fr.md)), la liste que vous définissez **remplace** celle par défaut :

```yaml
output:
  pagetypeformats:
    homepage: [html] # uniquement HTML, quelles que soient les valeurs par défaut
```

Si un [thème](../templates/7-extend.fr.md#theme) définit la même liste, les valeurs de votre liste sont **ajoutées** à celles du thème, car elles peuvent être nécessaires à son fonctionnement.

Pour **ajuster** la liste par défaut (ou celle du thème), utilisez plutôt un tableau associatif : `true` ajoute une valeur, `false` la retire.

```yaml
output:
  pagetypeformats:
    homepage:
      rss: true   # ajoute RSS
      atom: false # retire Atom
assets:
  images:
    responsive:
      widths:
        2560: true # ajoute une largeur
        480: false # retire une largeur
```
