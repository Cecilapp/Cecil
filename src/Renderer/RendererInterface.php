<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Renderer;

use Cecil\Builder;

/**
 * Interface RendererInterface.
 */
interface RendererInterface
{
    /**
     * Constructor.
     *
     * @param string|array $templatesPath
     * @param Builder      $buider
     */
    public function __construct($templatesPath, Builder $buider);

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
