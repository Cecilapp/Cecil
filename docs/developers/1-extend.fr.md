<!--
title: "Étendre Cecil"
description: "Créez des générateurs de pages, ajoutez des extensions Twig et post-traitez les fichiers de sortie."
date: 2026-03-27
updated: 2026-10-26
alias: documentation/etendre
path: documentation/developpeurs/etendre
-->
# Étendre Cecil

Comme Cecil est écrit en PHP, il est facile d’en étendre les capacités.

Les classes personnalisées sont chargées automatiquement depuis le répertoire `extensions` de votre site (ou d’un thème) : la classe `Cecil\Generator\MyGenerator` est chargée depuis `extensions/Cecil/Generator/MyGenerator.php`.

## Configuration

Les générateurs de pages, les extensions Twig et les post-processeurs de sortie se déclarent de la même façon, sous forme de liste indexée par nom :

```yaml
<clé>:
  <nom>: <classe>                                         # priorité par défaut (100)
  <nom>: { class: <classe>, priority: <int> }             # priorité explicite
  <nom>: false                                            # désactivé
```

- Les extensions sont exécutées par ordre croissant de priorité ; sans priorité, elles sont exécutées après les extensions intégrées (priorité `100`), dans leur ordre de déclaration.
- Une extension intégrée peut être désactivée (`Redirect: false`) ou déplacée (`Section: { priority: 45 }`) par son nom.
- Une extension qui ne peut pas être chargée (classe inconnue, mauvaise interface, option invalide) fait échouer le build. Pour simplement journaliser l’erreur et ignorer l’extension, désactivez le mode strict :

```yaml
extensions:
  strict: false
```

:::important
Depuis Cecil 9, les extensions doivent être indexées par nom : une priorité en clé (par ex. `35: Cecil\Generator\Database`) est refusée, utilisez `Database: { class: Cecil\Generator\Database, priority: 35 }` à la place.
:::

## Générateurs de pages

Un générateur permet de créer des pages sans fichiers Markdown (avec des données provenant d’une API ou d’une base de données, par exemple), ou de modifier des pages existantes.

Créez une nouvelle classe PHP dans l’espace de noms `Cecil\Generator`, héritant de `AbstractGenerator`, puis ajoutez-la à la liste [`pages.generators`](../configuration/4-pages.fr.md#pages-generators).

Les générateurs intégrés utilisent les priorités `10` à `90` : choisissez la priorité de votre générateur selon les pages dont il a besoin (par ex. après `Section` pour accéder aux pages de sections).

**Exemple :**

_/extensions/Cecil/Generator/DummyPage.php_

```php
<?php
namespace Cecil\Generator;

use Cecil\Collection\Page\Page;
use Cecil\Collection\Page\Type;

class DummyPage extends AbstractGenerator implements GeneratorInterface
{
    public function generate(): void
    {
        // crée une nouvelle page $page, puis l’ajoute à la collection du site
        $page = (new Page('my-page'))
            ->setType(Type::PAGE)
            ->setPath('mypage')
            ->setBodyHtml('<p>My page body</p>')
            ->setVariable('language', 'en')
            ->setVariable('title', 'My page')
            ->setVariable('date', new \DateTime())
            ->setVariable('menu', ['main' => ['weight' => 99]]);
        $this->generatedPages->add($page);
    }
}
```

_/extensions/Cecil/Generator/Database.php_

```php
<?php
namespace Cecil\Generator;

use Cecil\Collection\Page\Page;
use Cecil\Collection\Page\Type;

class Database extends AbstractGenerator implements GeneratorInterface
{
    public function generate(): void
    {
        // crée des pages à partir d’une base de données SQLite
        $db = new \SQLite3('database.sqlite');
        $statement = $db->prepare('SELECT * FROM blog');
        $result = $statement->execute();
        while ($row = $result->fetchArray(\SQLITE3_ASSOC)) {
            $page = (new Page($row['page-id']))
                ->setType(Type::PAGE)
                ->setPath($row['path'])
                ->setBodyHtml($row['html'])
                ->setVariable('title', $row['title'])
                ->setVariable('date', $row['date']);
            $this->generatedPages->add($page);
        }
        $result->finalize();
        $db->close();
    }
}
```

_configuration_

```yaml
pages:
  generators:
    DummyPage: Cecil\Generator\DummyPage
    Database: { class: Cecil\Generator\Database, priority: 35 }
```

## Extension Twig

Vous pouvez ajouter des [fonctions](../templates/reference/1-functions.fr.md) et des [filtres](../templates/reference/3-filters.fr.md) personnalisés :

1. [créez une extension Twig](https://twig.symfony.com/doc/advanced.html#creating-an-extension) dans l’espace de noms `Cecil\Renderer\Extension`
2. ajoutez le fichier PHP dans le répertoire `extensions`
3. ajoutez-la à la configuration [`layouts.extensions`](../configuration/7-layouts.fr.md#layouts-extensions)

Le constructeur de l’extension reçoit l’instance de `Cecil\Builder`, qui donne accès à la configuration et au contenu du site.

**Exemple :**

_/extensions/Cecil/Renderer/Extension/MyTwigExtension.php_

```php
<?php
namespace Cecil\Renderer\Extension;

class MyTwigExtension extends \Twig\Extension\AbstractExtension
{
    public function getFilters(): array
    {
        // ajoute un nouveau filtre nommé 'md5'
        return [
            new \Twig\TwigFilter('md5', 'md5'),
        ];
    }
}
```

_configuration_

```yaml
layouts:
  extensions:
    MyExtension: Cecil\Renderer\Extension\MyTwigExtension
```

## Post-processeurs de sortie

Vous pouvez post-traiter le rendu des pages.

Créez une nouvelle classe PHP dans l’espace de noms `Cecil\Renderer\PostProcessor`, héritant de `AbstractPostProcessor`, puis ajoutez-la à la liste [`output.postprocessors`](../configuration/8-output.fr.md#output-postprocessors).

Les post-processeurs intégrés (`GeneratorMetaTag`, `HtmlExcerpt` et `MarkdownLink`) utilisent les priorités `10` à `30` : ils sont donc exécutés en premier par défaut.

**Exemple :**

_/extensions/Cecil/Renderer/PostProcessor/MyProcessor.php_

```php
<?php
namespace Cecil\Renderer\PostProcessor;

use Cecil\Collection\Page\Page;

class MyProcessor extends AbstractPostProcessor
{
    public function process(Page $page, string $output, string $format): string
    {
        // ajoute une balise meta dans le head de la sortie HTML
        if ($format == 'html') {
            if (!preg_match('/<meta name="test".*/i', $output)) {
                $meta = '<meta name="test" content="Test">';
                $output = preg_replace_callback('/([[:blank:]]*)(<\/head>)/i', function ($matches) use ($meta) {
                    return str_repeat($matches[1] ?: ' ', 2) . $meta . "\n" . $matches[1] . $matches[2];
                }, $output);
            }
        }

        return $output;
    }
}
```

_configuration_

```yaml
output:
  postprocessors:
    MyProcessor: Cecil\Renderer\PostProcessor\MyProcessor
```
