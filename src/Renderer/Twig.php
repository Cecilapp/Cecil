<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Renderer;

use Cecil\Builder;
use Cecil\Collection\Page\Page;
use Cecil\Collection\Page\PrefixSuffix;
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
     * @var string
     */
    protected $rendered;

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
        $this->rendered = $this->twig->render($template, $variables);

        // add generator meta
        if (!preg_match('/<meta name="generator".*/i', $this->rendered)) {
            $meta = \sprintf('<meta name="generator" content="Cecil %s" />', Builder::getVersion());
            $this->rendered = preg_replace('/(<\/head>)/i', "\t$meta\n  $1", $this->rendered);
        }

        // replace excerpt or break tag by HTML anchor
        // https://regex101.com/r/Xl7d5I/3
        $pattern = '(.*)(<!--[[:blank:]]?(excerpt|break)[[:blank:]]?-->)(.*)';
        $replacement = '$1<span id="more"></span>$4';
        $this->rendered = preg_replace('/'.$pattern.'/is', $replacement, $this->rendered);

        // replace internal link to *.md files with the right URL
        // https://regex101.com/r/dZ02zO/5
        $this->rendered = preg_replace_callback(
            '/href="([A-Za-z0-9_\.\-\/]+)\.md(\#[A-Za-z0-9\-]+)?"/is',
            function ($matches) {
                return \sprintf('href="../%s%s"', Page::slugify(PrefixSuffix::sub($matches[1])), $matches[2] ?? '');
            },
            $this->rendered
        );

        return $this->rendered;
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
