<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Converter;

/**
 * Interface ConverterInterface.
 */
interface ConverterInterface
{
    /**
     * Converts frontmatter.
     *
     * @param  $string
     * @param  $type
     *
     * @return mixed
     */
    public static function convertFrontmatter($string, $type);

    /**
     * Converts body.
     *
     * @param  $string
     *
     * @return mixed
     */
    public static function convertBody($string);
}
