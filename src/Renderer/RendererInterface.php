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

/**
 * Interface RendererInterface.
 */
interface RendererInterface
{
    /**
     * @param string|array $templatesPath
     */
    public function __construct(Builder $buider, $templatesPath);

    /**
     * Adds a global variable.
     */
    public function addGlobal(string $name, $value): void;

    /**
     * Rendering.
     */
    public function render(string $template, array $variables): string;
}
