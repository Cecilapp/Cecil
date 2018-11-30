<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Renderer;

use PHPoole\Config;

/**
 * Interface RendererInterface.
 */
interface RendererInterface
{
    /**
     * Constructor.
     *
     * @param string|array $templatesPath
     * @param Config       $config
     */
    public function __construct($templatesPath, $config);

    /**
     * Add global variable.
     *
     * @param $name
     * @param $value
     *
     * @return void
     */
    public function addGlobal($name, $value);

    /**
     * Rendering.
     *
     * @param string $template
     * @param $variables
     *
     * @return self
     */
    public function render($template, $variables);

    /**
     * Validates template.
     *
     * @param $template
     *
     * @return bool
     */
    public function isValid($template);
}
