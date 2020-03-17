<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
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
    /**
     * @var \Twig\Environment
     */
    protected $twig;
    /**
     * @var string
     */
    protected $templatesDir;

    /**
     * {@inheritdoc}
     */
    public function __construct($templatesPath, Builder $builder)
    {
        // load layouts
        $loader = new \Twig\Loader\FilesystemLoader($templatesPath);
        // Twig
        $this->twig = new \Twig\Environment($loader, [
            'debug'            => true,
            'strict_variables' => true,
            'autoescape'       => false,
            'cache'            => false,
            'auto_reload'      => true,
        ]);
        // add extensions
        $this->twig->addExtension(new \Twig\Extension\DebugExtension());
        $this->twig->addExtension(new TwigExtension($builder));
        $this->twig->addExtension(new \Twig\Extension\StringLoaderExtension());
        // set date format & timezone
        $this->twig->getExtension(\Twig\Extension\CoreExtension::class)
            ->setDateFormat($builder->getConfig()->get('date.format'));
        $this->twig->getExtension(\Twig\Extension\CoreExtension::class)
            ->setTimezone($builder->getConfig()->get('date.timezone'));
        // Internationalisation
        if (extension_loaded('intl')) {
            $this->twig->addExtension(new \Twig_Extensions_Extension_Intl());
        }
        if (extension_loaded('gettext')) {
            $this->twig->addExtension(new \Twig_Extensions_Extension_I18n());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addGlobal($name, $value)
    {
        $this->twig->addGlobal($name, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function render($template, $variables)
    {
        return $this->twig->render($template, $variables);
    }

    /**
     * {@inheritdoc}
     */
    public function isValid($template)
    {
        try {
            $this->twig->parse($this->twig->tokenize($template));

            return true;
        } catch (\Twig\Error\SyntaxError $e) {
            return false;
        }
    }
}
