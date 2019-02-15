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
    public function __construct($templatesPath, Builder $buidler)
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
        $this->twig->addExtension(new TwigExtension($buidler));
        $this->twig->addExtension(new \Twig_Extension_StringLoader());
        $this->twig->getExtension('Twig_Extension_Core')->setDateFormat($buidler->getConfig()->get('site.date.format'));
        $this->twig->getExtension('Twig_Extension_Core')->setTimezone($buidler->getConfig()->get('site.date.timezone'));
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
            $this->rendered = preg_replace('/(<head>|<head[[:space:]]+.*>)/i', '$1'."\n\t".$meta, $this->rendered);
        }

        // replace excerpt tag by HTML anchor
        $pattern = '/(.*)(<!-- excerpt -->)(.*)/i';
        $replacement = '$1<span id="more"></span>$3';
        $this->rendered = preg_replace($pattern, $replacement, $this->rendered);

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
