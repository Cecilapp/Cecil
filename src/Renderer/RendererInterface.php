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
 * Renderer interface.
 */
interface RendererInterface
{
    /**
     * @param Builder      $builder
     * @param string|array<string> $templatesPath
     */
    public function __construct(Builder $builder, $templatesPath);

    /**
     * Adds a global variable.
     *
     * @param mixed $value
     */
    public function addGlobal(string $name, $value): void;

    /**
     * Rendering.
     *
     * @param array<string, mixed> $variables
     */
    public function render(string $template, array $variables): string;

    /**
     * Set locale (e.g.: 'fr_FR').
     */
    public function setLocale(string $locale): void;

    /**
     * Adds a translation file.
     *
     * @param array<string, array{loader: string, ext: array<string>}>|null $formatsConfig
     */
    public function addTransResource(string $translationsDir, string $locale, ?array $formatsConfig = null): void;
}
