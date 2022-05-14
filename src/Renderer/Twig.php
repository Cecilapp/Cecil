<?php

/*
 * This file is part of Cecil.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Renderer;

use Cecil\Builder;
use Cecil\Renderer\Twig\Extension as TwigExtension;

/**
 * Class Twig.
 */
class Twig implements RendererInterface
{
    /** @var \Twig\Environment */
    protected $twig;

    /** @var string */
    protected $templatesDir;

    /** @var \Twig\Profiler\Profile */
    public $profile;

    /**
     * {@inheritdoc}
     */
    public function __construct(Builder $builder, $templatesPath)
    {
        // load layouts
        $loader = new \Twig\Loader\FilesystemLoader($templatesPath);
        // default options
        $loaderOptions = [
            'debug'            => $builder->isDebug(),
            'strict_variables' => true,
            'autoescape'       => false,
            'auto_reload'      => true,
            'cache'            => false,
        ];
        // use Twig cache?
        if ($builder->getConfig()->get('cache.templates.enabled')) {
            $templatesCachePath = \Cecil\Util::joinFile(
                $builder->getConfig()->getCachePath(),
                (string) $builder->getConfig()->get('cache.templates.dir')
            );
            $loaderOptions = array_replace($loaderOptions, ['cache' => $templatesCachePath]);
        }
        // create the Twig instance
        $this->twig = new \Twig\Environment($loader, $loaderOptions);
        // set date format
        $this->twig->getExtension(\Twig\Extension\CoreExtension::class)
            ->setDateFormat($builder->getConfig()->get('date.format'));
        // set timezone
        if ($builder->getConfig()->has('date.timezone')) {
            $this->twig->getExtension(\Twig\Extension\CoreExtension::class)
                ->setTimezone($builder->getConfig()->get('date.timezone'));
        }
        // adds extensions
        $this->twig->addExtension(new TwigExtension($builder));
        $this->twig->addExtension(new \Twig\Extension\StringLoaderExtension());
        // internationalisation
        if (extension_loaded('intl')) {
            $this->twig->addExtension(new \Twig\Extensions\IntlExtension());
            $builder->getLogger()->debug('Intl extension is loaded');
        }
        if (extension_loaded('gettext')) {
            $this->twig->addExtension(new \Twig\Extensions\I18nExtension());
            $builder->getLogger()->debug('Gettext extension is loaded');
        }
        if ($builder->isDebug()) {
            // dump()
            $this->twig->addExtension(new \Twig\Extension\DebugExtension());
            // profiler
            $this->profile = new \Twig\Profiler\Profile();
            $this->twig->addExtension(new \Twig\Extension\ProfilerExtension($this->profile));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addGlobal(string $name, $value): void
    {
        $this->twig->addGlobal($name, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function render(string $template, array $variables): string
    {
        return $this->twig->render($template, $variables);
    }
}
