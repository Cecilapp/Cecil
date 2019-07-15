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
     * @var \Twig_Environment
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
        $loader = new \Twig_Loader_Filesystem($templatesPath);
        // Twig
        $this->twig = new \Twig_Environment($loader, [
            'debug'            => true,
            'strict_variables' => true,
            'autoescape'       => false,
            'cache'            => false,
            'auto_reload'      => true,
        ]);
        // add extensions
        $this->twig->addExtension(new \Twig_Extension_Debug());
        $this->twig->addExtension(new TwigExtension($builder));
        $this->twig->addExtension(new \Twig_Extension_StringLoader());
        // set date format & timezone
        $this->twig->getExtension('Twig_Extension_Core')->setDateFormat($builder->getConfig()->get('date.format'));
        $this->twig->getExtension('Twig_Extension_Core')->setTimezone($builder->getConfig()->get('date.timezone'));
        // Internationalisation
        $locale = $builder->getConfig()->getLanguageProperty('locale');
        // The PHP Intl extension is needed to use localized date
        if (extension_loaded('intl')) {
            $this->twig->addExtension(new \Twig_Extensions_Extension_Intl());
            if ($locale) {
                \Locale::setDefault($locale);
            }
        }
        // The PHP Gettext extension is needed to use translation
        if (extension_loaded('gettext')) {
            $this->twig->addExtension(new \Twig_Extensions_Extension_I18n());
            $localePath = realpath($builder->getConfig()->getSourceDir().'/locale');
            $domain = 'messages';
            putenv("LC_ALL=$locale");
            putenv("LANGUAGE=$locale");
            setlocale(LC_ALL, "$locale.UTF-8");
            bindtextdomain($domain, $localePath);
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
            $meta = '<meta name="generator" content="Cecil" />';
            $this->rendered = preg_replace('/(<\/head>)/i', "\t$meta\n$1", $this->rendered);
        }

        // replace excerpt or break tag by HTML anchor
        // https://regex101.com/r/Xl7d5I/3
        $pattern = '(.*)(<!--[[:blank:]]?(excerpt|break)[[:blank:]]?-->)(.*)';
        $replacement = '$1<span id="more"></span>$4';
        $this->rendered = preg_replace('/'.$pattern.'/is', $replacement, $this->rendered);

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
        } catch (\Twig_Error_Syntax $e) {
            return false;
        }
    }
}
