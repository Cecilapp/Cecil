<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Converter;

use Cecil\Builder;

/**
 * Interface ConverterInterface.
 */
interface ConverterInterface
{
    /**
     * Converts frontmatter.
     *
     * @param string $string
     * @param string $type
     *
     * @return array
     */
    public static function convertFrontmatter($string, $type): array;

    /**
     * Converts body.
     *
     * @param string       $string
     * @param Builder|null $builder
     *
     * @return string
     */
    public static function convertBody($string, $builder = null): string;
}
