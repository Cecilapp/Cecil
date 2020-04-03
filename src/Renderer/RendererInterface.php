<?php
/**
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
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
     * @param string|array $templatesPath
     * @param Builder      $buider
     */
    public function __construct($templatesPath, Builder $buider);

    /**
     * Adds a global variable.
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return void
     */
    public function addGlobal(string $name, $value);

    /**
     * Rendering.
     *
     * @param string $template
     * @param array  $variables
     *
     * @return self
     */
    public function render(string $template, array $variables);
}
