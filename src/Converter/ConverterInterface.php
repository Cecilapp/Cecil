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

namespace Cecil\Converter;

/**
 * Converter interface.
 */
interface ConverterInterface
{
    /**
     * Converts front matter.
     */
    public function convertFrontmatter(string $string, string $format): array;

    /**
     * Converts body.
     */
    public function convertBody(string $string): string;
}
