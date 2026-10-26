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

namespace Cecil\Plugin;

/**
 * Plugin interface.
 *
 * A plugin is the single entry point of a set of extensions (generators, Twig extensions, post-processors,
 * event subscribers and listeners, default configuration), declared once in the `extensions.plugins` configuration.
 */
interface PluginInterface
{
    /**
     * Registers the plugin extensions.
     */
    public function register(Registry $registry): void;
}
