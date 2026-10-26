# Extensions and Plugins

Custom PHP classes live in `extensions/` (or `themes/<theme>/extensions/`), autoloaded by Cecil: the file path must match the class namespace (e.g. `extensions/MyProject/Generator/CustomGenerator.php`), unless a PSR-4 mapping is declared (`extensions.autoload: {'MyProject\': src/}`, relative to the site or theme directory). Use your own namespace: `Cecil\` is reserved (deprecated for custom classes, reported as a warning). Third-party libraries installed with Composer in `extensions/` (`composer require <package> --working-dir=extensions`) are loaded from `extensions/vendor/autoload.php`, also with the phar.

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

Register it with `pages.generators`, indexed by name (lower priority runs first, default priority is `100`):

```yaml
pages:
  generators:
    Custom: { class: MyProject\Generator\CustomGenerator, priority: 35 } # after ExternalBody (30), before Section (40)
```

Built-in generators are named `DefaultPages` (10), `ExternalBody` (30), `Section` (40), `Taxonomy` (50), `Homepage` (60), `Pagination` (70), `Alias` (80) and `Redirect` (90): disable one with `<Name>: false`, move one with `<Name>: { priority: <int> }`. A priority as key (`35: <class>`) is rejected since Cecil 9. A generator that must be listed in sections, taxonomies or paginated lists has to run before them.

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

Post-process the rendered output of each page by extending `AbstractPostProcessor` (built-in post-processors use the priorities 10 to 30, so they run first by default):

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

## Markdown Converter

Front matter and Markdown body are converted by the class set in `pages.converter` (default `Cecil\Converter\Converter`, also used by the `markdown_to_html` filter and external pages; `toc` still uses the built-in parser). Replace it with a class implementing `Cecil\Converter\ConverterInterface` (constructor receives the `Builder`), typically extending `Cecil\Converter\Converter` and overriding `convertBody()` to pre/post-process Markdown.

## Build Events

Run code around the build, each step or each page with an event subscriber extending `Cecil\Event\AbstractSubscriber` (`$this->builder`, `$this->config`), registered with `extensions.subscribers`:

```php
<?php

namespace MyProject;

use Cecil\Event\AbstractSubscriber;
use Cecil\Event\BuildEvents;
use Cecil\Event\PageRenderedEvent;

class Banner extends AbstractSubscriber
{
    public static function getSubscribedEvents(): array
    {
        return [BuildEvents::PAGE_RENDERED => 'onPageRendered'];
    }

    public function onPageRendered(PageRenderedEvent $event): void
    {
        if ($event->getFormat() === 'html') {
            $event->setOutput(str_replace('<body>', '<body><div class="banner">Beta</div>', $event->getOutput()));
        }
    }
}
```

```yaml
extensions:
  subscribers:
    Banner: MyProject\Banner
```

Events (`Cecil\Event\BuildEvents`): `build.start` / `build.finished` (`BuildEvent`), `step.pre` / `step.post` (`StepEvent`, `getStep()`), `page.created` (from a content file) / `page.converted` (published pages) (`PageEvent`, `getPage()`), `page.rendered` (`PageRenderedEvent`, `getFormat()`, `getOutput()`, `setOutput()`, after post-processors), `page.saved` (`PageSavedEvent`, `getFormat()`, `getPathname()`). With Cecil as a library, use `$builder->getEventDispatcher()->addListener(...)` before `build()`.

## Plugins

A plugin registers a set of extensions from a single class extending `Cecil\Plugin\AbstractPlugin`, declared once in `extensions.plugins` (the recommended way for a theme to ship extensions, in its `config.yml`):

```php
<?php

namespace MyTheme;

use Cecil\Event\BuildEvents;
use Cecil\Plugin\AbstractPlugin;
use Cecil\Plugin\Registry;

class MyThemePlugin extends AbstractPlugin
{
    public function register(Registry $registry): void
    {
        $registry
            ->config(['mytheme' => ['option' => true]])          // default options
            ->twigExtension(Twig\MyExtension::class)              // `layouts.extensions.MyExtension`
            ->generator(Generator\Feed::class, priority: 35)      // `pages.generators.Feed`
            ->postProcessor(PostProcessor\Minify::class)           // `output.postprocessors.Minify`
            ->subscriber(SearchIndex::class)                       // `extensions.subscribers.SearchIndex`
            ->listen(BuildEvents::BUILD_FINISHED, fn () => null);      // event listener
    }
}
```

```yaml
extensions:
  plugins:
    MyTheme: MyTheme\MyThemePlugin
```

Registered extensions are named after their short class name (or `name:`), with the default priority `100` (or `priority:`). The plugin configuration has the lowest precedence: site and themes configuration can override its options and disable (`Feed: false`) or move (`Feed: {priority: 45}`) its extensions.

## Extensions Configuration

Generators, Twig extensions, post-processors, event subscribers and plugins share the same syntax: `<name>: <class>`, `<name>: { class: <class>, priority: <int> }` or `<name>: false`. If an extension can't be loaded (unknown class, wrong interface, invalid option), the build fails with `Unable to load extension "<name>" (<config key>)`; set `extensions.strict: false` to only log the error and skip the extension.

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
