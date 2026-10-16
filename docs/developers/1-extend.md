<!--
title: "Extend Cecil"
description: "Create custom page generators, add Twig extensions and post-process output files."
date: 2023-04-17
updated: 2026-10-16
alias: documentation/extend
-->
# Extend Cecil

Because Cecil is written in PHP, it's easy to extend its capabilities.

Custom classes are autoloaded from the `extensions` directory of your site (or of a theme): the class `Cecil\Generator\MyGenerator` is loaded from `extensions/Cecil/Generator/MyGenerator.php`.

## Page generators

A generator helps you create pages without Markdown files (for example, with data from an API or a database) or alter existing pages.

Create a new PHP class in the `Cecil\Generator` namespace, extending `AbstractGenerator`, and add the class name to the [`pages.generators`](../configuration/4-pages.md#pages-generators) list.

Generators are run in ascending order of their key (priority). Built-in generators use the keys `10` to `90`: use another key, otherwise the built-in generator is replaced.

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
    # priority: class name
    99: Cecil\Generator\DummyPage
    35: Cecil\Generator\Database
```

## Twig extension

You can add custom [functions](../templates/reference/1-functions.md) and [filters](../templates/reference/3-filters.md):

1. [create a Twig extension](https://twig.symfony.com/doc/advanced.html#creating-an-extension) in the `Cecil\Renderer\Extension` namespace
2. add the PHP file in the `extensions` directory
3. add the class name to the [`layouts.extensions`](../configuration/7-layouts.md#layouts-extensions) configuration

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

Create a new PHP class in the `Cecil\Renderer\PostProcessor` namespace, extending `AbstractPostProcessor`, and add the class name to the [`output.postprocessors`](../configuration/8-output.md#output-postprocessors) list.

Built-in post-processors (`GeneratorMetaTag`, `HtmlExcerpt` and `MarkdownLink`) are run first.

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
