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

namespace Cecil\Renderer\Twig;

use Cecil\Builder;
use Cecil\Config;
use Cecil\Renderer\Twig;
use Psr\Log\LoggerInterface;

/**
 * Factory to create and configure the Twig Renderer.
 *
 * This factory allows creating the renderer with all its dependencies
 * in a lazy manner (only when needed).
 */
class TwigFactory
{
    private Config $config;

    /**
     * Logger instance for future use.
     * Reserved for consistency with other factory patterns in the codebase.
     */
    private LoggerInterface $logger;

    private Builder $builder;

    public function __construct(
        Config $config,
        LoggerInterface $logger,
        Builder $builder
    ) {
        $this->config = $config;
        $this->logger = $logger;
        $this->builder = $builder;
    }

    /**
     * Creates a Twig renderer instance.
     *
     * @param string|array|null $templatesPath Template path(s) (uses default config if null)
     * @return Twig Configured renderer instance
     */
    public function create(string|array|null $templatesPath = null): Twig
    {
        if ($templatesPath === null) {
            $templatesPath = $this->config->getLayoutsPath();
        }

        return new Twig($this->builder, $templatesPath);
    }
}
