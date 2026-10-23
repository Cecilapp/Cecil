<!--
title: "Contenu pour les LLM"
description: "Générez un fichier llms.txt et des versions Markdown de vos pages pour les modèles de langage."
date: 2026-10-22
updated: 2026-10-23
path: documentation/ia/llms
-->
# Contenu pour les LLM

Les modèles de langage lisent plus facilement le Markdown que le HTML. Cecil leur fournit deux [formats de sortie](../configuration/8-output.fr.md#formats-par-defaut) :

- `llms` : un fichier [`llms.txt`](https://llmstxt.org), index du site ;
- `markdown` : une version Markdown de chaque page.

Les deux sont désactivés par défaut.

## llms.txt

Depuis la version ++8.114.0++, le format `llms` génère la page d’accueil sous la forme d’un fichier `llms.txt` à la racine du site. Il contient :

- le titre et la description du site ;
- les liens du menu principal (les URL externes sont ignorées) ;
- les sections de premier niveau et leurs pages (les sous-sections sont imbriquées), avec leur titre et leur description.

Pour l’activer, ajoutez le format à la page d’accueil :

```yaml
output:
  pagetypeformats:
    homepage:
      llms: true
```

Avec le template [metatags](../configuration/1-site.fr.md#metatags), la page d’accueil HTML référence le fichier avec une balise `<link rel="describedby" type="text/markdown">`.

### En-têtes HTTP

Vous pouvez également signaler le fichier aux robots et aux agents avec des en-têtes HTTP, via l’option [`server.headers`](../configuration/10-server.fr.md#serverheaders) :

```yaml
server:
  headers:
    - path: /*
      headers:
        # LLMs
        - key: Link
          value: '</llms.txt>; rel="llms-txt"'
        - key: X-Llms-Txt
          value: /llms.txt
```

Ces en-têtes ne sont appliqués que par le serveur de prévisualisation local. Pour les déployer, utilisez le [thème composant _Headers_](https://github.com/Cecilapp/theme-headers) : il génère un fichier `_headers` (pris en charge par Netlify, statichost et Cloudflare Pages) incluant les en-têtes définis dans `server.headers`.

### Exclure du contenu

Pour exclure une page ou une section (et ses pages) de `llms.txt`, définissez `llms: false` dans son front matter :

```yaml
---
title: "Mentions légales"
llms: false
---
```

Les sections non publiées et [exclues](../content/2-front-matter.fr.md#excluded) ne sont pas listées.

## Pages Markdown

Le format `markdown` génère une page sous la forme d’un fichier Markdown, à côté de sa version HTML (ex. : `blog/post-1.md` pour `blog/post-1/index.html`). Le fichier contient le titre et le corps de la page ; une section liste ses pages.

Pour générer toutes les pages et sections en Markdown :

```yaml
output:
  pagetypeformats:
    page:
      markdown: true
    section:
      markdown: true
```

Ou pour une seule page, avec la variable [`output`](../content/2-front-matter.fr.md#output) :

```yaml
---
output: [html, markdown]
---
```

Avec le template [metatags](../configuration/1-site.fr.md#metatags), la page HTML référence sa version Markdown avec une balise `<link rel="alternate" type="text/markdown">`. Les liens de `llms.txt` pointent vers la version Markdown des pages lorsqu’elle existe.

## Templates

Pour personnaliser ces sorties, surchargez les templates par défaut dans votre répertoire `layouts` (voir les [règles de recherche](../templates/1-lookup-rules.fr.md)) :

- `home.llms.twig` : le fichier `llms.txt` ;
- `page.markdown.twig`, `list.markdown.twig` et `home.markdown.twig` : les pages Markdown.
