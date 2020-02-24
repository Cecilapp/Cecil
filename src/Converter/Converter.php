<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Converter;

use Cecil\Exception\Exception;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Class Converter.
 */
class Converter implements ConverterInterface
{
    /**
     * {@inheritdoc}
     */
    public static function convertFrontmatter($string, $type = 'yaml'): array
    {
        switch ($type) {
            case 'ini':
                return parse_ini_string($string);
            case 'yaml':
            default:
                try {
                    return Yaml::parse((string) $string);
                } catch (ParseException $e) {
                    throw new Exception($e->getMessage());
                }
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function convertBody($string, $config = null): string
    {
        $parsedown = new Parsedown($config);

        return $parsedown->text($string);
    }
}
