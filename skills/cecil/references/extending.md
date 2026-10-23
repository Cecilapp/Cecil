# Extensions and Plugins

Custom PHP classes live in `extensions/` (or `themes/<theme>/extensions/`), autoloaded by Cecil: the file path must match the class namespace (e.g. `extensions/MyProject/Generator/CustomGenerator.php`).

> Note: use single backslashes for class names in YAML. Double backslashes (`\\`) are only needed inside JSON or PHP strings.

## Custom Generators

A generator creates pages (from an API, a database, data files, etc.) and adds them to `$this->generatedPages`:

```php
<?php

namespace MyProject\Generator;

use Cecil\Collection\Page\Page;
use Cecil\Collection\Page\Type;
use Cecil\Generator\AbstractGenerator;
use Cecil\Generator\GeneratorInterface;

class CustomGenerator extends AbstractGenerator implements GeneratorInterface
{
    public function generate(): void
    {
        $page = (new Page('my-page'))         // page ID
            ->setType(Type::PAGE)
            ->setPath('my-page')
            ->setBodyHtml('<p>My page body</p>')
            ->setVariable('title', 'My page')
            ->setVariable('date', new \DateTime())
            ->setVariable('menu', ['main' => ['weight' => 99]]);
        $this->generatedPages->add($page);
    }
}
```

Register it with `pages.generators`, keyed by priority (lower runs first):

```yaml
pages:
  generators:
    35: MyProject\Generator\CustomGenerator # after ExternalBody (30), before Section (40)
```

Built-in generators use the keys `10`, `30`, `40`, `50`, `60`, `70`, `80` and `90`: reusing one **replaces** that built-in generator. A generator that must be listed in sections, taxonomies or paginated lists has to run before them.

## Twig Extensions

Add functions, filters or tests with a [Twig extension](https://twig.symfony.com/doc/advanced.html#creating-an-extension), registered with `layouts.extensions`:

```php
<?php

namespace MyProject\Twig;

use Cecil\Builder;

class MyExtension extends \Twig\Extension\AbstractExtension
{
    public function __construct(private Builder $builder) // the Builder gives access to the config and the content
    {
    }

    public function getFilters(): array
    {
        return [
            new \Twig\TwigFilter('md5', 'md5'),
        ];
    }
}
```

```yaml
layouts:
  extensions:
    MyExtension: MyProject\Twig\MyExtension
```

The class is instantiated with the `Cecil\Builder` instance as constructor argument (`$builder->getConfig()`, `$builder->getPages()`).

## Output Post-processors

Post-process the rendered output of each page by extending `AbstractPostProcessor` (built-in post-processors run first):

```php
<?php

namespace MyProject\Renderer\PostProcessor;

use Cecil\Collection\Page\Page;
use Cecil\Renderer\PostProcessor\AbstractPostProcessor;

class MyProcessor extends AbstractPostProcessor
{
    public function process(Page $page, string $output, string $format): string
    {
        if ($format === 'html') {
            $output = str_replace('</head>', '<meta name="test" content="Test"></head>', $output);
        }

        return $output;
    }
}
```

```yaml
output:
  postprocessors:
    MyProcessor: MyProject\Renderer\PostProcessor\MyProcessor
```

If a Twig extension or post-processor class can't be loaded, the build doesn't fail: an error is logged (`Unable to add Twig extension`, `Unable to load output post processor`), visible with `-v`.

## Use Cecil as a PHP Library

```bash
composer require cecil/cecil
```

```php
<?php

require_once 'vendor/autoload.php';

use Cecil\Builder;

Builder::create(['title' => 'My website', 'baseurl' => 'https://domain.tld/']) // array or Cecil\Config, optional PSR-3 logger
    ->setSourceDir(__DIR__)      // website sources (current directory by default)
    ->setDestinationDir(__DIR__) // where `_site` is created (source directory by default)
    ->build(['drafts' => false, 'dry-run' => false, 'page' => '', 'render-subset' => '']);
```

To process images with libvips, also `composer require intervention/image-driver-vips` (requires libvips and the `ffi` extension).

The doctors are also available programmatically: `Cecil\Doctor\SiteDoctor`, `FrontmatterDoctor`, `SeoDoctor` and `LinksDoctor` (see the [library documentation](https://cecil.app/documentation/developers/library/)).

## Code Quality Standards

When extending or contributing to Cecil:

- Follow PSR-12 coding standards
- Use `declare(strict_types=1);` in all PHP files
- Include proper PHPDoc blocks for all classes and methods
- Use 4-space indentation for PHP, 2-space for YAML/Twig
