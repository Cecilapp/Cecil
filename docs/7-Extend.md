<!--
description: "Extend Cecil."
date: 2023-04-17
updated: 2025-03-27
-->
# Extend

Because Cecil is powered by PHP it's easy to extend its capabilities.

[toc]

## Pages Generator

A Generator help you to create pages without Markdown files (with data from an API or a database for example) or alter existing pages.

Just create a new PHP class in the `Cecil\Generator` namespace and add the class name to the [`pages.generators`](4-Configuration.md#pages-generators) list.

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
            ->setType(Type::PAGE->value)
            ->setPath('mypage')
            ->setBodyHtml('<p>My page body</p>')
            ->setVariable('language', 'en')
            ->setVariable('title', 'My page')
            ->setVariable('date', now())
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
        $db = new SQLite3('database.sqlite');
        $statement = $db->prepare('SELECT * FROM blog');
        $result = $statement->execute();
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $page = (new Page($row['page-id']))
                ->setType(Type::PAGE->value)
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

You can add custom [functions](3-Templates.md#functions) and [filters](3-Templates.md#filters):

1. [create a Twig extension](https://twig.symfony.com/doc/advanced.html#creating-an-extension) in the `Cecil\Renderer\Extension` namespace
2. add the PHP file in the `extensions` directory
3. add the class name to the configuration

**Example:**

_/extensions/Cecil/Renderer/Extension/MyTwigExtension.php_

```php
<?php
namespace Cecil\Renderer\Extension;

class MyTwigExtension extends \Twig\Extension\AbstractExtension
{
    public function getFilters()
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

## Output Post Processor

You can post process page output.

Just create a new PHP class in the `Cecil\Renderer\PostProcessor` namespace and add the class name to the `output.postprocessors list.

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
                $meta = \sprintf('<meta name="test" content="Test">');
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
