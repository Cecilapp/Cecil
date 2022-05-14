<?php declare(strict_types=1);

/*
 * This file is part of Cecil.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Converter;

/**
 * Interface ConverterInterface.
 */
interface ConverterInterface
{
    /**
     * Converts frontmatter.
     */
    public function convertFrontmatter(string $string, string $type): array;

    /**
     * Converts body.
     */
    public function convertBody(string $string): string;
}
