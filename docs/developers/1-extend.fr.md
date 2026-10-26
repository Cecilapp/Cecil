<!--
title: "Étendre Cecil"
description: "Créez des générateurs de pages, ajoutez des extensions Twig, post-traitez les fichiers de sortie et écoutez les événements de build."
date: 2026-03-27
updated: 2026-10-26
alias: documentation/etendre
path: documentation/developpeurs/etendre
-->
# Étendre Cecil

Comme Cecil est écrit en PHP, il est facile d’en étendre les capacités.

:::tip
Utilisez la commande [`new:extension`](../commands/6-new-extension.fr.md) pour créer le squelette d’une extension : `cecil new:extension --type=generator --name=Database --namespace=MySite`.
:::

## Chargement automatique

Les classes personnalisées sont chargées automatiquement depuis le répertoire `extensions` de votre site (ou d’un thème), le chemin du fichier correspondant à l’espace de noms : la classe `MySite\Generator\MyGenerator` est chargée depuis `extensions/MySite/Generator/MyGenerator.php`.

Pour utiliser une autre organisation, déclarez des correspondances [PSR-4](https://www.php-fig.org/psr/psr-4/), relatives au répertoire du site (ou du thème) :

```yaml
extensions:
  autoload:
    'MySite\': src/ # `MySite\Generator\MyGenerator` est chargée depuis `src/Generator/MyGenerator.php`
```

Si vos extensions dépendent de bibliothèques tierces, installez-les avec [Composer](https://getcomposer.org) dans le répertoire `extensions` (`composer require <paquet> --working-dir=extensions`) : `extensions/vendor/autoload.php` est chargé automatiquement, y compris avec le phar de Cecil. Les bibliothèques déjà fournies par Cecil ne sont pas remplacées.

:::important
Utilisez votre propre espace de noms (par ex. `MySite\` ou le nom de votre thème) : l’espace de noms `Cecil\` est réservé, et son utilisation pour des classes personnalisées est dépréciée.
:::

## Configuration

Les générateurs de pages, les extensions Twig, les post-processeurs de sortie, les abonnés aux [événements de build](#evenements-de-build) et les [plugins](#plugins) se déclarent de la même façon, sous forme de liste indexée par nom :

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
Depuis Cecil 9, les extensions doivent être indexées par nom : une priorité en clé (par ex. `35: MySite\Generator\Database`) est refusée, utilisez `Database: { class: MySite\Generator\Database, priority: 35 }` à la place.
:::

## Générateurs de pages

Un générateur permet de créer des pages sans fichiers Markdown (avec des données provenant d’une API ou d’une base de données, par exemple), ou de modifier des pages existantes.

Créez une nouvelle classe PHP héritant de `Cecil\Generator\AbstractGenerator`, puis ajoutez-la à la liste [`pages.generators`](../configuration/4-pages.fr.md#pages-generators).

Les générateurs intégrés utilisent les priorités `10` à `90` : choisissez la priorité de votre générateur selon les pages dont il a besoin (par ex. après `Section` pour accéder aux pages de sections).

Un générateur ajoute des pages à `$this->generatedPages` (une page ayant l’ID d’une page existante la remplace), et peut supprimer des pages existantes avec `$this->removePage('<id-de-page>')`.

**Exemple :**

_/extensions/MySite/Generator/DummyPage.php_

```php
<?php
namespace MySite\Generator;

use Cecil\Collection\Page\Page;
use Cecil\Collection\Page\Type;
use Cecil\Generator\AbstractGenerator;
use Cecil\Generator\GeneratorInterface;

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

_/extensions/MySite/Generator/Database.php_

```php
<?php
namespace MySite\Generator;

use Cecil\Collection\Page\Page;
use Cecil\Collection\Page\Type;
use Cecil\Generator\AbstractGenerator;
use Cecil\Generator\GeneratorInterface;

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
    DummyPage: MySite\Generator\DummyPage
    Database: { class: MySite\Generator\Database, priority: 35 }
```

## Extension Twig

Vous pouvez ajouter des [fonctions](../templates/reference/1-functions.fr.md) et des [filtres](../templates/reference/3-filters.fr.md) personnalisés :

1. [créez une extension Twig](https://twig.symfony.com/doc/advanced.html#creating-an-extension)
2. ajoutez le fichier PHP dans le répertoire `extensions` (voir [Chargement automatique](#chargement-automatique))
3. ajoutez-la à la configuration [`layouts.extensions`](../configuration/7-layouts.fr.md#layouts-extensions)

Le constructeur de l’extension reçoit l’instance de `Cecil\Builder`, qui donne accès à la configuration et au contenu du site.

**Exemple :**

_/extensions/MySite/Twig/MyTwigExtension.php_

```php
<?php
namespace MySite\Twig;

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
    MyExtension: MySite\Twig\MyTwigExtension
```

## Post-processeurs de sortie

Vous pouvez post-traiter le rendu des pages.

Créez une nouvelle classe PHP héritant de `Cecil\Renderer\PostProcessor\AbstractPostProcessor`, puis ajoutez-la à la liste [`output.postprocessors`](../configuration/8-output.fr.md#output-postprocessors).

Les post-processeurs intégrés (`GeneratorMetaTag`, `HtmlExcerpt` et `MarkdownLink`) utilisent les priorités `10` à `30` : ils sont donc exécutés en premier par défaut.

**Exemple :**

_/extensions/MySite/PostProcessor/MyProcessor.php_

```php
<?php
namespace MySite\PostProcessor;

use Cecil\Collection\Page\Page;
use Cecil\Renderer\PostProcessor\AbstractPostProcessor;

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
    MyProcessor: MySite\PostProcessor\MyProcessor
```

## Convertisseur Markdown

Le front matter et le corps Markdown des pages sont convertis par la classe définie dans [`pages.converter`](../configuration/4-pages.fr.md#pages-converter). Elle est aussi utilisée par le filtre `markdown_to_html` et pour les pages externes (le filtre `toc` utilise directement l’[analyseur Markdown](#analyseur-markdown)).

Pour modifier la conversion, créez une classe PHP implémentant `Cecil\Converter\ConverterInterface` (son constructeur reçoit l’instance de `Cecil\Builder`), ou héritant de la classe par défaut `Cecil\Converter\Converter`.

**Exemple :**

_/extensions/MySite/Converter.php_

```php
<?php
namespace MySite;

class Converter extends \Cecil\Converter\Converter
{
    public function convertBody(string $string, ?string $language = null): string
    {
        // remplace `[[page-id]]` par un lien, avant la conversion par défaut
        $string = preg_replace('/\[\[([a-z0-9-\/]+)\]\]/', '[$1]($1.md)', $string);

        return parent::convertBody($string, $language);
    }
}
```

_configuration_

```yaml
pages:
  converter: MySite\Converter
```

## Analyseur Markdown

Pour ajouter votre propre syntaxe Markdown, remplacez l’analyseur Markdown utilisé par le convertisseur par défaut (et par les filtres `markdown_to_html` et `toc`) avec l’option [`pages.body.parser`](../configuration/4-pages.fr.md#pages-body-parser).

Créez une classe PHP héritant de `Cecil\Converter\Parsedown`, basée sur [Parsedown](https://github.com/erusev/parsedown) (1.x) et [Parsedown Extra](https://github.com/erusev/parsedown-extra), et déclarez votre syntaxe avec son mécanisme d’extension : un caractère déclencheur dans `$this->InlineTypes` (ou `$this->BlockTypes`) fait référence à une méthode `inline<Type>()` (ou aux méthodes `block<Type>()`, `block<Type>Continue()` et `block<Type>Complete()`).

:::warning
Un analyseur personnalisé repose sur le fonctionnement interne de Parsedown : il peut nécessiter une mise à jour quand Cecil met à jour Parsedown.
:::

**Exemple :**

_/extensions/MySite/Parsedown.php_

```php
<?php
namespace MySite;

use Cecil\Builder;

class Parsedown extends \Cecil\Converter\Parsedown
{
    public function __construct(Builder $builder, ?array $options = null)
    {
        parent::__construct($builder, $options);
        // `==texte==` → `<mark>texte</mark>`
        $this->InlineTypes['='][] = 'Mark';
        $this->inlineMarkerList .= '=';
    }

    protected function inlineMark($Excerpt)
    {
        if (preg_match('/^==(?=\S)(.+?)(?<=\S)==/', $Excerpt['text'], $matches)) {
            return [
                'extent'  => \strlen($matches[0]),
                'element' => ['name' => 'mark', 'text' => $matches[1], 'handler' => 'line'],
            ];
        }

        return null;
    }
}
```

_configuration_

```yaml
pages:
  body:
    parser: MySite\Parsedown
```

## Événements de build

Des événements sont déclenchés pendant le build : écoutez-les pour exécuter votre propre code avant ou après le build, chaque étape, ou à chaque étape du cycle de vie d’une page (par ex. pour créer un index de recherche, notifier un service ou modifier la sortie des pages).

| Événement        | Constante                     | Quand                                                                | Classe d’événement  |
|------------------|-------------------------------|----------------------------------------------------------------------|---------------------|
| `build.start`    | `BuildEvents::BUILD_START`    | avant la première étape                                              | `BuildEvent`        |
| `build.finished` | `BuildEvents::BUILD_FINISHED` | après la dernière étape                                              | `BuildEvent`        |
| `step.pre`       | `BuildEvents::STEP_PRE`       | avant chaque étape                                                   | `StepEvent`         |
| `step.post`      | `BuildEvents::STEP_POST`      | après chaque étape                                                   | `StepEvent`         |
| `page.created`   | `BuildEvents::PAGE_CREATED`   | après la création d’une page à partir d’un fichier de contenu        | `PageEvent`         |
| `page.converted` | `BuildEvents::PAGE_CONVERTED` | après la conversion d’une page publiée (front matter et corps)       | `PageEvent`         |
| `page.rendered`  | `BuildEvents::PAGE_RENDERED`  | après le rendu d’une page dans un format (après post-processeurs)    | `PageRenderedEvent` |
| `page.saved`     | `BuildEvents::PAGE_SAVED`     | après l’enregistrement d’une page rendue dans un fichier             | `PageSavedEvent`    |

Chaque événement donne accès au contexte du build (`getBuilder()`) et à ses options (`getOptions()`) ; `StepEvent` ajoute `getStep()`, `PageEvent` ajoute `getPage()`, `PageRenderedEvent` ajoute `getFormat()`, `getOutput()` et `setOutput()`, `PageSavedEvent` ajoute `getFormat()` et `getPathname()`.

Créez un [abonné aux événements](https://symfony.com/doc/current/components/event_dispatcher.html#using-event-subscribers) héritant de `Cecil\Event\AbstractSubscriber` (qui donne accès à `$this->builder` et `$this->config`), puis ajoutez-le à la liste `extensions.subscribers`.

**Exemple :**

_/extensions/MySite/SearchIndex.php_

```php
<?php
namespace MySite;

use Cecil\Event\AbstractSubscriber;
use Cecil\Event\BuildEvent;
use Cecil\Event\BuildEvents;
use Cecil\Event\PageEvent;

class SearchIndex extends AbstractSubscriber
{
    private array $index = [];

    public static function getSubscribedEvents(): array
    {
        return [
            BuildEvents::PAGE_CONVERTED => 'onPageConverted',
            BuildEvents::BUILD_FINISHED => 'onBuildFinished',
        ];
    }

    public function onPageConverted(PageEvent $event): void
    {
        $page = $event->getPage();
        $this->index[] = ['title' => $page->getVariable('title'), 'path' => $page->getPath()];
    }

    public function onBuildFinished(BuildEvent $event): void
    {
        if ($event->getOptions()['dry-run']) {
            return;
        }
        file_put_contents($this->config->getOutputPath() . '/search.json', json_encode($this->index));
    }
}
```

_configuration_

```yaml
extensions:
  subscribers:
    SearchIndex: MySite\SearchIndex
```

:::tip
En utilisant Cecil comme [bibliothèque PHP](2-library.fr.md), des écouteurs peuvent aussi être ajoutés directement au dispatcher avant le build : `$builder->getEventDispatcher()->addListener(BuildEvents::PAGE_SAVED, $callable)`.
:::

## Plugins

Un plugin est un point d’entrée unique qui enregistre un ensemble d’extensions : générateurs, extensions Twig, post-processeurs, abonnés et écouteurs d’événements, et configuration par défaut. C’est la façon recommandée de fournir des extensions avec un thème : activer le thème active son plugin, sans autre configuration.

Créez une classe PHP héritant de `Cecil\Plugin\AbstractPlugin` (qui donne accès à `$this->builder` et `$this->config`), puis ajoutez-la à la liste `extensions.plugins`.

**Exemple :**

_/themes/example/extensions/Example/ExamplePlugin.php_

```php
<?php
namespace Example;

use Cecil\Event\BuildEvents;
use Cecil\Plugin\AbstractPlugin;
use Cecil\Plugin\Registry;

class ExamplePlugin extends AbstractPlugin
{
    public function register(Registry $registry): void
    {
        $registry
            ->config(['example' => ['feed' => ['limit' => 10]]]) // options par défaut
            ->twigExtension(Twig\ExampleExtension::class)
            ->generator(Generator\Feed::class, priority: 35)
            ->postProcessor(PostProcessor\Minify::class)
            ->subscriber(SearchIndex::class)
            ->listen(BuildEvents::BUILD_FINISHED, fn () => $this->builder->getLogger()->notice('Example is ready'));
    }
}
```

_/themes/example/config.yml_

```yaml
extensions:
  plugins:
    Example: Example\ExamplePlugin
```

Les extensions enregistrées par un plugin sont nommées d’après le nom court de leur classe (par ex. `Feed`), sauf si un `name` est fourni, et utilisent la priorité par défaut (`100`), sauf si une `priority` est fournie. La configuration du plugin a la priorité la plus faible : la configuration du site (et des thèmes) peut remplacer ses options, et désactiver (`Feed: false`) ou déplacer (`Feed: { priority: 45 }`) ses extensions par leur nom.
