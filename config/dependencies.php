<?php

declare(strict_types=1);

use Cecil\Converter\Converter;
use Cecil\Converter\Parsedown;
use Cecil\Generator\Alias;
use Cecil\Generator\DefaultPages;
use Cecil\Generator\ExternalBody;
use Cecil\Generator\GeneratorManager;
use Cecil\Generator\Homepage;
use Cecil\Generator\Pagination;
use Cecil\Generator\Redirect;
use Cecil\Generator\Section;
use Cecil\Generator\Taxonomy;
use Cecil\Generator\VirtualPages;
use Cecil\Cache;
use Cecil\Renderer\Extension\Core as CoreExtension;
use Cecil\Renderer\Twig;
use Cecil\Renderer\Twig\TwigFactory;
use Cecil\Step\Assets\Save as AssetsSave;
use Cecil\Step\Data\Load as DataLoad;
use Cecil\Step\Menus\Create as MenusCreate;
use Cecil\Step\Optimize\Css as OptimizeCss;
use Cecil\Step\Optimize\Html as OptimizeHtml;
use Cecil\Step\Optimize\Images as OptimizeImages;
use Cecil\Step\Optimize\Js as OptimizeJs;
use Cecil\Step\Pages\Convert;
use Cecil\Step\Pages\Create as PagesCreate;
use Cecil\Step\Pages\Generate;
use Cecil\Step\Pages\Load as PagesLoad;
use Cecil\Step\Pages\Render;
use Cecil\Step\Pages\Save as PagesSave;
use Cecil\Step\StaticFiles\Copy as StaticFilesCopy;
use Cecil\Step\StaticFiles\Load as StaticFilesLoad;
use Cecil\Step\Taxonomies\Create as TaxonomiesCreate;
use Psr\Log\LoggerInterface;

use function DI\autowire;
use function DI\get;

/**
 * Dependencies configuration for PHP-DI.
 *
 * @see https://php-di.org/doc/php-definitions.html
 */
return [
    /*
     * Converters
     */
    // Parsedown : injecte Config et Builder
    Parsedown::class => autowire()
        ->constructorParameter('config', get(\Cecil\Config::class))
        ->constructorParameter('options', null),
    // Converter : injecte automatiquement Parsedown
    Converter::class => autowire()
        ->constructorParameter('parsedown', get(Parsedown::class)),

    /*
     * Generators
     */
    // GeneratorManager : injecte Builder, Config et Logger
    GeneratorManager::class => autowire(),
    // Individual generators
    ExternalBody::class => autowire(),
    VirtualPages::class => autowire(),
    Homepage::class => autowire(),
    Section::class => autowire(),
    Taxonomy::class => autowire(),
    Pagination::class => autowire(),
    Alias::class => autowire(),
    Redirect::class => autowire(),
    DefaultPages::class => autowire(),

    /*
     * Build lifecycle steps
     */
    // Phase 1: Load
    PagesLoad::class => autowire(),
    DataLoad::class => autowire(),
    StaticFilesLoad::class => autowire(),
    // Phase 2: Create
    PagesCreate::class => autowire(),
    TaxonomiesCreate::class => autowire(),
    MenusCreate::class => autowire(),
    // Phase 3: Process
    Convert::class => autowire(),
    Generate::class => autowire(),
    Render::class => autowire(),
    // Phase 4: Copy & Save
    StaticFilesCopy::class => autowire(),
    PagesSave::class => autowire(),
    AssetsSave::class => autowire(),
    // Phase 5: Optimize
    OptimizeHtml::class => autowire(),
    OptimizeCss::class => autowire(),
    OptimizeJs::class => autowire(),
    OptimizeImages::class => autowire(),

    /*
     * Services
     */
    // Twig Factory: for lazy loading of template engine
    TwigFactory::class => autowire(),

    // Twig Renderer: constructed via factory
    Twig::class => \DI\factory(function (TwigFactory $factory) {
        return $factory->create();
    }),

    // Cache: factory to create cache instances with different pools
    Cache::class => \DI\factory(function (\Cecil\Builder $builder) {
        return new Cache($builder, '');
    }),

    // Twig Extensions: singleton for better performance
    CoreExtension::class => \DI\autowire()->lazy(),

    // Config and Logger will be dynamically injected by Builder
    // (see ContainerFactory::create)
];
