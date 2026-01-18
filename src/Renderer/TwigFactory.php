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

namespace Cecil\Renderer;

use Cecil\Builder;

/**
 * TwigFactory class.
 *
 * Factory for creating Twig renderer instances with proper configuration.
 */
class TwigFactory
{
    /**
     * Creates a Twig renderer instance.
     *
     * @param Builder $builder The builder instance
     * @param array|string $templatesPath Templates path(s)
     * @return Twig The configured Twig renderer
     */
    public function create(Builder $builder, $templatesPath): Twig
    {
        return new Twig($builder, $templatesPath);
    }
}
