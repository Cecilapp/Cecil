<?php

/**
 * This file is part of Cecil.
 *
 * (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

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
    \Cecil\Converter\Parsedown::class => autowire()
        ->constructorParameter('config', get(\Cecil\Config::class))
        ->constructorParameter('options', null),
    // Converter : injecte automatiquement Parsedown
    \Cecil\Converter\Converter::class => autowire()
        ->constructorParameter('parsedown', get(\Cecil\Converter\Parsedown::class)),

    /*
     * Generators
     */
    // GeneratorManager : injecte Builder, Config et Logger
    \Cecil\Generator\GeneratorManager::class => autowire(),
    // Individual generators
    \Cecil\Generator\ExternalBody::class => autowire(),
    \Cecil\Generator\VirtualPages::class => autowire(),
    \Cecil\Generator\Homepage::class => autowire(),
    \Cecil\Generator\Section::class => autowire(),
    \Cecil\Generator\Taxonomy::class => autowire(),
    \Cecil\Generator\Pagination::class => autowire(),
    \Cecil\Generator\Alias::class => autowire(),
    \Cecil\Generator\Redirect::class => autowire(),
    \Cecil\Generator\DefaultPages::class => autowire(),

    /*
     * Build lifecycle steps
     */
    // Phase 1: Load
    \Cecil\Step\Pages\Load::class => autowire(),
    \Cecil\Step\Data\Load::class => autowire(),
    \Cecil\Step\StaticFiles\Load::class => autowire(),
    // Phase 2: Create
    \Cecil\Step\Pages\Create::class => autowire(),
    \Cecil\Step\Taxonomies\Create::class => autowire(),
    \Cecil\Step\Menus\Create::class => autowire(),
    // Phase 3: Process
    \Cecil\Step\Pages\Convert::class => autowire(),
    \Cecil\Step\Pages\Generate::class => autowire(),
    \Cecil\Step\Pages\Render::class => autowire(),
    // Phase 4: Copy & Save
    \Cecil\Step\StaticFiles\Copy::class => autowire(),
    \Cecil\Step\Pages\Save::class => autowire(),
    \Cecil\Step\Assets\Save::class => autowire(),
    // Phase 5: Optimize
    \Cecil\Step\Optimize\Html::class => autowire(),
    \Cecil\Step\Optimize\Css::class => autowire(),
    \Cecil\Step\Optimize\Js::class => autowire(),
    \Cecil\Step\Optimize\Images::class => autowire(),

    /*
     * Services
     */
    // Twig Factory: for lazy loading of template engine
    \Cecil\Renderer\Twig\TwigFactory::class => autowire(),

    // Twig Renderer: constructed via factory
    \Cecil\Renderer\Twig::class => DI\factory(function (\Cecil\Renderer\Twig\TwigFactory $factory) {
        return $factory->create();
    }),

    // Cache: factory to create cache instances with different pools
    \Cecil\Cache::class => DI\factory(function (\Cecil\Builder $builder) {
        return new \Cecil\Cache($builder, '');
    }),

    // Twig Extensions: singleton for better performance
    \Cecil\Renderer\Extension\Core::class => DI\autowire()->lazy(),

    // Config and Logger will be dynamically injected by Builder
    // (see ContainerFactory::create)
];
