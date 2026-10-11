<!--
title: "doctor"
description: "Diagnostiquer la configuration, le front matter, le SEO et les liens."
date: 2026-03-27
updated: 2026-10-11
path: documentation/commandes/doctor
-->
# doctor

Diagnostique le site courant et l'environnement Cecil.

## Utilisation

```plaintext
Description:
  Diagnoses the site configuration

Usage:
  doctor [options] [--] [<path>]

Arguments:
  path                       Use the given path as working directory

Options:
  -c, --config=CONFIG        Set the path to an extra configuration file
  -h, --help                 Display help for the given command. When no command is given display help for the list command
  -q, --quiet                Do not output any message
  -V, --version              Display this application version
      --ansi|--no-ansi       Force (or disable --no-ansi) ANSI output
  -n, --no-interaction       Do not ask any interactive question
  -v|vv|vvv, --verbose       Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  The doctor command diagnoses the current site and Cecil environment.

    cecil.phar doctor
    cecil.phar doctor path/to/the/working/directory

  To inspect a site with an extra configuration file, run:

    cecil.phar doctor --config=config.yml
```

## doctor:frontmatter

Valide la syntaxe du front matter des pages.

```plaintext
Description:
  Validates pages front matter syntax

Usage:
  doctor:frontmatter|doctor:fm [options] [--] [<path>]

Arguments:
  path                  Use the given path as working directory

Options:
  -c, --config=CONFIG   Set the path to an extra configuration file
  -p, --page=PAGE       Validate a single page relative to the pages directory
      --ansi|--no-ansi  Force (or disable --no-ansi) ANSI output
  -n, --no-interaction  Do not ask any interactive question
  -h, --help            Display help for the given command. When no command is given display help for the list command
  -q, --quiet           Do not output any message
  -V, --version         Display this application version
  -v|vv|vvv             Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
```

## doctor:seo

Audite les pages HTML rendues pour détecter les problèmes SEO courants.

```plaintext
Description:
  Audits rendered HTML pages for common SEO issues

Usage:
  doctor:seo [options] [--] [<path>]

Arguments:
  path                  Use the given path as working directory

Options:
  -c, --config=CONFIG   Set the path to an extra configuration file
  -p, --page=PAGE       Audit a single page relative to the pages directory
      --format=FORMAT   Output format: text (default) or json
      --feedback        Include findings with feedback level
      --include-virtual Include virtual pages (paginated, taxonomies) in audit
      --fail-on=FAIL-ON Return a failure exit code when findings of this level or higher are found: bad (default), ok, feedback or never
```

La commande construit le site en mode dry-run, puis audite le HTML rendu avec un jeu de contrôles ciblés : balise title, meta description, URL canonique, structure des titres, balises Open Graph, attributs alt des images et longueur estimée du contenu.

Par défaut, les pages virtuelles (paginées, pages de taxonomie) sont exclues de l'audit. Utilisez `--include-virtual` pour les inclure.

Par défaut, les findings de niveau `feedback` ne sont pas listés.

Utilisez `--feedback` pour inclure les findings de niveau `feedback` en plus des autres findings.

Exportez les résultats en JSON pour l'intégration CI en utilisant `--format=json`.

Par défaut, la commande retourne un code de sortie en échec (`1`) lorsque des findings de niveau `bad` sont trouvés. Utilisez `--fail-on=ok` pour échouer également sur les findings `ok`, `--fail-on=feedback` pour échouer sur n'importe quel finding, ou `--fail-on=never` pour toujours réussir.

### Configuration

Personnalisez les seuils d'audit et les contrôles activés dans votre fichier de configuration :

```yaml
doctor:
  seo:
    title: { min: 30, max: 60 }
    description: { min: 120, max: 160 }
    content: { min_words: 300 }
    checks:
      title: true
      description: true
      canonical: true
      h1: true
      og_tags: true
      img_alt: true
      content_length: true
      lang_attribute: true
```

## doctor:links

Contrôle les liens internes et externes des pages rendues.

```plaintext
Description:
  Checks internal and external links of the rendered pages

Usage:
  doctor:links [options] [--] [<path>]

Arguments:
  path                           Use the given path as working directory

Options:
  -c, --config=CONFIG            Set the path to an extra configuration file
  -p, --page=PAGE                Check links of a single page relative to the pages directory
      --internal                 Check internal links
      --external                 Check external links
      --exclude=EXCLUDE          Exclude links matching this regular expression (multiple values allowed)
      --timeout=TIMEOUT          External links request timeout, in seconds
      --concurrency=CONCURRENCY  Number of concurrent external links requests
      --no-cache                 Do not use cached external links results
      --format=FORMAT            Output format: text (default) or json
      --include-virtual          Include virtual pages (paginated, taxonomies) in audit
      --feedback                 Include findings with feedback level
      --fail-on=FAIL-ON          Return a failure exit code when findings of this level or higher are found: bad (default), ok, feedback or never
```

La commande construit le site en mode dry-run, puis contrôle les liens trouvés dans les pages HTML rendues : liens (`<a>`, `<area>`, `<link>`), images (`src` et `srcset`), scripts, iframes, médias et styles en ligne. Les fichiers CSS liés depuis les pages sont également analysés (`url()` et `@import`).

- **Liens internes** : ils doivent cibler une page générée, un fichier statique ou un asset. Les fragments (`#ancre`) doivent correspondre à l'ID d'un élément de la page cible. Les URL absolues commençant par `baseurl` sont considérées comme des liens internes.
- **Liens externes** : ils sont requêtés en HTTP (`HEAD`, puis `GET` si le serveur refuse `HEAD`), en parallèle. Les résultats en succès sont mis en cache (24 heures par défaut) : utilisez `--no-cache` pour les contrôler à nouveau.

Par défaut, seuls les liens internes sont contrôlés :

```bash
php cecil.phar doctor:links                       # liens internes (par défaut)
php cecil.phar doctor:links --external            # liens externes uniquement
php cecil.phar doctor:links --internal --external # les deux
```

Niveaux des findings :

| Niveau     | Signification                                                                                         |
|------------|-------------------------------------------------------------------------------------------------------|
| `bad`      | Lien cassé : cible introuvable, ancre manquante, erreur HTTP (4xx, 5xx), hôte introuvable, etc.        |
| `ok`       | Lien fonctionnel mais améliorable : redirection, slash final manquant                                  |
| `feedback` | Lien invérifiable : accès refusé (401, 403, 429), probablement par une protection anti-bot, ou timeout |

Les liens utilisant d'autres schémas que HTTP (ex. : `mailto:`, `tel:`, `data:`) sont ignorés. Utilisez `--exclude` (expression régulière, insensible à la casse) pour ignorer d'autres liens.

Les options `--page`, `--include-virtual`, `--feedback`, `--format` et `--fail-on` se comportent comme avec `doctor:seo` (voir ci-dessus).

:::info
Le contrôle des liens externes nécessite l'extension PHP cURL.
:::

### Configuration

Personnalisez les réglages par défaut dans votre fichier de configuration :

```yaml
doctor:
  links:
    internal: true    # contrôle des liens internes
    external: false   # contrôle des liens externes
    timeout: 10       # timeout des requêtes des liens externes, en secondes
    concurrency: 10   # nombre de requêtes simultanées des liens externes
    cache_ttl: 86400  # durée de mise en cache des résultats en succès des liens externes, en secondes
    exclude: []       # liens à ignorer (liste d'expressions régulières), ex. : ['linkedin\.com', '^https://localhost']
    useragent: ~      # en-tête User-Agent des requêtes des liens externes (`assets.remote.useragent.default` par défaut)
```
