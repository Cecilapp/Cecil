<!--
description: "Extend Cecil."
date: 2023-04-17
-->
# Extend

There is several way to extend Cecil:

1. Pages Generator
2. Templates extension
3. Output post processor

## Pages Generator

You can create a custom Generator to create "virtuals" pages (without Markdown files) or alter existing pages.

Just add your Generator to the [`pages.generators`](4-Configuration.md#generators) list, and create a new class in the `Cecil\Generator` namespace.

**Example:**

_/generators/Cecil/Generator/MyGenerator.php_

```php
<?php
namespace Cecil\Generator;

use Cecil\Collection\Page\Page;
use Cecil\Collection\Page\Type;

class MyGenerator extends AbstractGenerator implements GeneratorInterface
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
          ->setVariable('date', now())
          ->setVariable('menu', ['main' => ['weight' => 99]]);
        $this->generatedPages->add($page);
    }
}
```

## Templates extension

You can add custom [functions](3-Templates.md#functions) and [filters](3-Templates.md#filters):

1. [create a Twig extension](https://twig.symfony.com/doc/advanced.html#creating-an-extension) in the `Cecil\Renderer\Extension` namespace
2. add the PHP file in the `extensions` directory
3. add the class name to the configuration

**Example:**

_/extensions/Cecil/Renderer/Extension/MyExtension.php_

```php
<?php
namespace Cecil\Renderer\Extension;

class MyExtension extends \Twig\Extension\AbstractExtension
{
    public function getFilters()
    {
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
    MyExtension: Cecil\Renderer\Extension\MyExtension
```

## Output post processor

You can post process page output.

Just add your post processor to the `output.postprocessors` list, and create a new class in the `Cecil\Renderer\PostProcessor` namespace.

**Example:**

_/postprocessors/Cecil/Renderer/PostProcessor/MyProcessor.php_

```php
<?php
namespace Cecil\Renderer\PostProcessor;

class MyProcessor extends AbstractPostProcessor
{
    public function process(Page $page, string $output, string $format): string
    {
        // handle $output

        return $output;
    }
}
```
