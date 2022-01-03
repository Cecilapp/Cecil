<?php
/**
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Converter;

use Cecil\Builder;
use Cecil\Exception\RuntimeException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Class Converter.
 */
class Converter implements ConverterInterface
{
    /** @var Builder */
    protected $builder;

    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     */
    public function convertFrontmatter(string $string, string $type = 'yaml'): array
    {
        switch ($type) {
            case 'ini':
                $result = parse_ini_string($string);
                if ($result === false) {
                    throw new RuntimeException('Can\'t parse INI front matter.');
                }

                return $result;
            case 'yaml':
            default:
                try {
                    $result = Yaml::parse((string) $string) ?? [];

                    return $result;
                } catch (ParseException $e) {
                    throw new RuntimeException($e->getMessage());
                }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function convertBody(string $string): string
    {
        try {
            $parsedown = new Parsedown($this->builder);
        } catch (\Exception $e) {
            throw new RuntimeException('Can\'t convert Markdown.');
        }

        return $parsedown->text($string);
    }
}
