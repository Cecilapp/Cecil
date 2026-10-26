<!--
title: "Extend Cecil"
description: "Create custom page generators, add Twig extensions, post-process output files and listen to build events."
date: 2023-04-17
updated: 2026-10-26
alias: documentation/extend
-->
# Extend Cecil

Because Cecil is written in PHP, it's easy to extend its capabilities.

:::tip
Use the [`new:extension`](../commands/6-new-extension.md) command to create the skeleton of an extension: `cecil new:extension --type=generator --name=Database --namespace=MySite`.
:::

## Autoloading

Custom classes are autoloaded from the `extensions` directory of your site (or of a theme), the file path matching the namespace: the class `MySite\Generator\MyGenerator` is loaded from `extensions/MySite/Generator/MyGenerator.php`.

To use another layout, declare [PSR-4](https://www.php-fig.org/psr/psr-4/) mappings, relative to the site (or theme) directory:

```yaml
extensions:
  autoload:
    'MySite\': src/ # `MySite\Generator\MyGenerator` is loaded from `src/Generator/MyGenerator.php`
```

If your extensions depend on third-party libraries, install them with [Composer](https://getcomposer.org) in the `extensions` directory (`composer require <package> --working-dir=extensions`): `extensions/vendor/autoload.php` is loaded automatically, even with the Cecil phar. Libraries already provided by Cecil are not overridden.

:::important
Use your own namespace (e.g.: `MySite\` or the name of your theme): the `Cecil\` namespace is reserved, and using it for custom classes is deprecated.
:::

## Configuration

Page generators, Twig extensions, output post-processors, [build events](#build-events) subscribers and [plugins](#plugins) are declared the same way, as a map indexed by name:

```yaml
<key>:
  <name>: <class>                                         # default priority (100)
  <name>: { class: <class>, priority: <int> }             # explicit priority
  <name>: false                                           # disabled
```

- Extensions run in ascending order of their priority; without a priority, they run after the built-in ones (priority `100`), in their declaration order.
- A built-in extension can be disabled (`Redirect: false`) or moved (`Section: { priority: 45 }`) by its name.
- An extension that cannot be loaded (unknown class, wrong interface, invalid option) fails the build. To log the error and skip the extension instead, disable the strict mode:

```yaml
extensions:
  strict: false
```

:::important
Since Cecil 9, extensions must be indexed by name: a priority as key (e.g.: `35: MySite\Generator\Database`) is rejected, use `Database: { class: MySite\Generator\Database, priority: 35 }` instead.
:::

## Page generators

A generator helps you create pages without Markdown files (for example, with data from an API or a database) or alter existing pages.

Create a new PHP class extending `Cecil\Generator\AbstractGenerator`, and add it to the [`pages.generators`](../configuration/4-pages.md#pages-generators) list.

Built-in generators use the priorities `10` to `90`: choose the priority of your generator depending on the pages it needs (e.g.: after `Section` to access sections pages).

A generator adds pages to `$this->generatedPages` (a page with the ID of an existing page replaces it), and can remove existing pages with `$this->removePage('<page-id>')`.

**Example:**

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
        // create a new page $page, then add it to the site collection
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
        // create pages from a SQLite database
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

## Twig extension

You can add custom [functions](../templates/reference/1-functions.md) and [filters](../templates/reference/3-filters.md):

1. [create a Twig extension](https://twig.symfony.com/doc/advanced.html#creating-an-extension)
2. add the PHP file in the `extensions` directory (see [Autoloading](#autoloading))
3. add it to the [`layouts.extensions`](../configuration/7-layouts.md#layouts-extensions) configuration

The extension constructor receives the `Cecil\Builder` instance, which gives access to the configuration and the site content.

**Example:**

_/extensions/MySite/Twig/MyTwigExtension.php_

```php
<?php
namespace MySite\Twig;

class MyTwigExtension extends \Twig\Extension\AbstractExtension
{
    public function getFilters(): array
    {
        // add a new filter named 'md5'
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

## Output post-processors

You can post-process the rendered output of pages.

Create a new PHP class extending `Cecil\Renderer\PostProcessor\AbstractPostProcessor`, and add it to the [`output.postprocessors`](../configuration/8-output.md#output-postprocessors) list.

Built-in post-processors (`GeneratorMetaTag`, `HtmlExcerpt` and `MarkdownLink`) use the priorities `10` to `30`, so they run first by default.

**Example:**

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
        // add a meta tag to the head of the HTML output
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

## Markdown converter

The front matter and the Markdown body of pages are converted by the class defined in [`pages.converter`](../configuration/4-pages.md#pages-converter). It is also used by the `markdown_to_html` filter and for external pages (the `toc` filter uses the [Markdown parser](#markdown-parser) directly).

To change the conversion, create a PHP class implementing `Cecil\Converter\ConverterInterface` (its constructor receives the `Cecil\Builder` instance), or extending the default `Cecil\Converter\Converter` class.

**Example:**

_/extensions/MySite/Converter.php_

```php
<?php
namespace MySite;

class Converter extends \Cecil\Converter\Converter
{
    public function convertBody(string $string, ?string $language = null): string
    {
        // replaces `[[page-id]]` with a link, before the default conversion
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

## Markdown parser

To add your own Markdown syntax, replace the Markdown parser used by the default converter (and by the `markdown_to_html` and `toc` filters) with the [`pages.body.parser`](../configuration/4-pages.md#pages-body-parser) option.

Create a PHP class extending `Cecil\Converter\Parsedown`, based on [Parsedown](https://github.com/erusev/parsedown) (1.x) and [Parsedown Extra](https://github.com/erusev/parsedown-extra), and register your syntax with its extension mechanism: a marker character in `$this->InlineTypes` (or `$this->BlockTypes`) refers to an `inline<Type>()` method (or `block<Type>()`, `block<Type>Continue()` and `block<Type>Complete()` methods).

:::warning
A custom parser relies on Parsedown internals: it may need to be updated when Cecil upgrades Parsedown.
:::

**Example:**

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
        // `==text==` → `<mark>text</mark>`
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

## Build events

Events are dispatched during the build: listen to them to run your own code before or after the build, each step, or at each step of the life cycle of a page (e.g.: to create a search index, notify a service, or modify the output of pages).

| Event            | Constant                      | When                                                         | Event class         |
|------------------|-------------------------------|--------------------------------------------------------------|---------------------|
| `build.start`    | `BuildEvents::BUILD_START`    | before the first step                                        | `BuildEvent`        |
| `build.finished` | `BuildEvents::BUILD_FINISHED` | after the last step                                          | `BuildEvent`        |
| `step.pre`       | `BuildEvents::STEP_PRE`       | before each step                                             | `StepEvent`         |
| `step.post`      | `BuildEvents::STEP_POST`      | after each step                                              | `StepEvent`         |
| `page.created`   | `BuildEvents::PAGE_CREATED`   | after a page is created from a content file                  | `PageEvent`         |
| `page.converted` | `BuildEvents::PAGE_CONVERTED` | after a published page is converted (front matter and body)  | `PageEvent`         |
| `page.rendered`  | `BuildEvents::PAGE_RENDERED`  | after a page is rendered in a format (after post-processors) | `PageRenderedEvent` |
| `page.saved`     | `BuildEvents::PAGE_SAVED`     | after a rendered page is saved to a file                     | `PageSavedEvent`    |

Every event gives access to the build context (`getBuilder()`) and options (`getOptions()`); `StepEvent` adds `getStep()`, `PageEvent` adds `getPage()`, `PageRenderedEvent` adds `getFormat()`, `getOutput()` and `setOutput()`, `PageSavedEvent` adds `getFormat()` and `getPathname()`.

Create an [event subscriber](https://symfony.com/doc/current/components/event_dispatcher.html#using-event-subscribers) extending `Cecil\Event\AbstractSubscriber` (which gives access to `$this->builder` and `$this->config`), and add it to the `extensions.subscribers` list.

**Example:**

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
When using Cecil as a [PHP library](2-library.md), listeners can also be added directly to the dispatcher before the build: `$builder->getEventDispatcher()->addListener(BuildEvents::PAGE_SAVED, $callable)`.
:::

## Plugins

A plugin is a single entry point that registers a set of extensions: generators, Twig extensions, post-processors, event subscribers and listeners, and default configuration. It's the recommended way to ship extensions with a theme: enabling the theme enables its plugin, with no other configuration.

Create a PHP class extending `Cecil\Plugin\AbstractPlugin` (which gives access to `$this->builder` and `$this->config`), and add it to the `extensions.plugins` list.

**Example:**

_/themes/paper/extensions/Paper/PaperPlugin.php_

```php
<?php
namespace Paper;

use Cecil\Event\BuildEvents;
use Cecil\Plugin\AbstractPlugin;
use Cecil\Plugin\Registry;

class PaperPlugin extends AbstractPlugin
{
    public function register(Registry $registry): void
    {
        $registry
            ->config(['paper' => ['feed' => ['limit' => 10]]]) // default options
            ->twigExtension(Twig\PaperExtension::class)
            ->generator(Generator\Feed::class, priority: 35)
            ->postProcessor(PostProcessor\Minify::class)
            ->subscriber(SearchIndex::class)
            ->listen(BuildEvents::BUILD_FINISHED, fn () => $this->builder->getLogger()->notice('Paper is ready'));
    }
}
```

_/themes/paper/config.yml_

```yaml
extensions:
  plugins:
    Paper: Paper\PaperPlugin
```

Extensions registered by a plugin are named after their short class name (e.g.: `Feed`), unless a `name` is given, and use the default priority (`100`) unless a `priority` is given. The plugin configuration has the lowest precedence: the site (and themes) configuration can override its options, and disable (`Feed: false`) or move (`Feed: { priority: 45 }`) its extensions by name.
