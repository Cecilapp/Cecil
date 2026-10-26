<!--
title: "Extend Cecil"
description: "Create custom page generators, add Twig extensions, post-process output files and listen to build events."
date: 2023-04-17
updated: 2026-10-26
alias: documentation/extend
-->
# Extend Cecil

Because Cecil is written in PHP, it's easy to extend its capabilities.

Custom classes are autoloaded from the `extensions` directory of your site (or of a theme): the class `Cecil\Generator\MyGenerator` is loaded from `extensions/Cecil/Generator/MyGenerator.php`.

## Configuration

Page generators, Twig extensions, output post-processors and [build events](#build-events) subscribers are declared the same way, as a map indexed by name:

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
Since Cecil 9, extensions must be indexed by name: a priority as key (e.g.: `35: Cecil\Generator\Database`) is rejected, use `Database: { class: Cecil\Generator\Database, priority: 35 }` instead.
:::

## Page generators

A generator helps you create pages without Markdown files (for example, with data from an API or a database) or alter existing pages.

Create a new PHP class in the `Cecil\Generator` namespace, extending `AbstractGenerator`, and add it to the [`pages.generators`](../configuration/4-pages.md#pages-generators) list.

Built-in generators use the priorities `10` to `90`: choose the priority of your generator depending on the pages it needs (e.g.: after `Section` to access sections pages).

**Example:**

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
    DummyPage: Cecil\Generator\DummyPage
    Database: { class: Cecil\Generator\Database, priority: 35 }
```

## Twig extension

You can add custom [functions](../templates/reference/1-functions.md) and [filters](../templates/reference/3-filters.md):

1. [create a Twig extension](https://twig.symfony.com/doc/advanced.html#creating-an-extension) in the `Cecil\Renderer\Extension` namespace
2. add the PHP file in the `extensions` directory
3. add it to the [`layouts.extensions`](../configuration/7-layouts.md#layouts-extensions) configuration

The extension constructor receives the `Cecil\Builder` instance, which gives access to the configuration and the site content.

**Example:**

_/extensions/Cecil/Renderer/Extension/MyTwigExtension.php_

```php
<?php
namespace Cecil\Renderer\Extension;

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
    MyExtension: Cecil\Renderer\Extension\MyTwigExtension
```

## Output post-processors

You can post-process the rendered output of pages.

Create a new PHP class in the `Cecil\Renderer\PostProcessor` namespace, extending `AbstractPostProcessor`, and add it to the [`output.postprocessors`](../configuration/8-output.md#output-postprocessors) list.

Built-in post-processors (`GeneratorMetaTag`, `HtmlExcerpt` and `MarkdownLink`) use the priorities `10` to `30`, so they run first by default.

**Example:**

_/extensions/Cecil/Renderer/PostProcessor/MyProcessor.php_

```php
<?php
namespace Cecil\Renderer\PostProcessor;

use Cecil\Collection\Page\Page;

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
    MyProcessor: Cecil\Renderer\PostProcessor\MyProcessor
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
