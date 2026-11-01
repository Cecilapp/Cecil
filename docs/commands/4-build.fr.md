<!--
title: "build"
description: "Générer le site."
date: 2026-03-27
updated: 2026-11-01
path: documentation/commandes/build
-->
# build

Génère le site.

## Utilisation

```plaintext
Description:
  Builds the website

Usage:
  build [options] [--] [<path>]

Arguments:
  path                               Use the given path as working directory

Options:
  -d, --drafts                       Include drafts
  -u, --baseurl=BASEURL              Set the base URL
  -o, --output=OUTPUT                Set the output directory
      --optimize|--no-optimize       Enable (or disable --no-optimize) optimization of generated files
      --dry-run                      Build without saving
  -c, --config=CONFIG                Set the path to extra config files (comma-separated)
      --clear-cache[=CLEAR-CACHE]    Clear cache before build (optional cache key as regular expression) [default: false]
  -p, --page=PAGE                    Build a specific page
      --render-subset=RENDER-SUBSET  Render a subset of pages
      --show-pages                   Show list of built pages in a table
  -m, --metrics                      Show build metrics (duration and memory) of each step
      --notify                       Send desktop notification on build completion
  -h, --help                         Display help for the given command. When no command is given display help for the list command
      --silent                       Do not output any message
  -q, --quiet                        Only errors are displayed. All other output is suppressed
  -V, --version                      Display this application version
      --ansi|--no-ansi               Force (or disable --no-ansi) ANSI output
  -n, --no-interaction               Do not ask any interactive question
  -v|vv|vvv, --verbose               Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  The build command generates the website in the output directory.

    cecil.phar build
    cecil.phar build path/to/the/working/directory
    cecil.phar build --baseurl=https://example.com/
    cecil.phar build --output=_site

  To build the website with optimization of generated files, you can use the --optimize option.
  This is useful to reduce the size of the generated files and improve performance:

    cecil.phar build --optimize
    cecil.phar build --no-optimize

  To build the website without overwriting files in the output directory, you can use the --dry-run option.
  This is useful to check what would be built without actually writing files:

    cecil.phar build --dry-run

  To build the website with a specific subset of rendered pages, you can use the --render-subset option.
  This is useful to build only a part of the website, for example, only "hot" pages or a specific section:

    cecil.phar build --render-subset=subset

  To show build steps metrics, run:

    cecil.phar build --metrics

  Send a desktop notification on build completion, run:

    cecil.phar build --notify
```

## Mettre en ligne {#deploy}

Par défaut, le site est généré dans le répertoire **`_site`** et **peut être déployé tel quel** sur n’importe quel serveur web ou service d’hébergement statique.

Pour générer et déployer le site automatiquement à chaque push sur un dépôt Git, Cecil fournit :

- un **script de build**, à utiliser comme commande de build sur une plateforme Jamstack ou un service d’intégration continue :

  ```bash
  curl -sSOL https://cecil.app/build.sh && bash ./build.sh
  ```

  Il télécharge Cecil (ou son [binaire autonome](../getting-started/2-installation.fr.md#standalone-binary) si PHP n’est pas disponible), installe les thèmes et génère le site, en déduisant le contexte de build (`production` ou `preview`) et l’URL de base à partir de la plateforme.  
  Définissez la variable d’environnement `CECIL_ENV` pour forcer le contexte de build, `CECIL_VERSION` pour utiliser une version précise de Cecil et `CECIL_CMD_OPTIONS` pour ajouter des options à la commande `build` (ex. : `--optimize`) ; les autres variables préfixées par `CECIL_` surchargent la [configuration](../configuration/index.fr.md) (ex. : `CECIL_OUTPUT_DIR`).
- une **GitHub Action**, [Cecil Action](https://github.com/Cecilapp/Cecil-Action), pour générer le site dans un workflow GitHub Actions : la commande [`new:site`](1-new-site.fr.md) peut créer un workflow qui déploie sur GitHub Pages.

:::tip
Voir [Mettre en ligne](https://cecil.app/fr/documentation/mettre-en-ligne/) pour des configurations prêtes à l’emploi (Netlify, Vercel, Cloudflare Pages, GitHub Pages, GitLab CI, etc.).
:::
