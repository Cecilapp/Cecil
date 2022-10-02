<?php

declare(strict_types=1);

/*
 * This file is part of Cecil.
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
use Yosymfony\Toml\Exception\ParseException as TomlParseException;
use Yosymfony\Toml\Toml;

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
    public function convertFrontmatter(string $string, string $format = 'yaml'): array
    {
        switch ($format) {
            // https://wikipedia.org/wiki/INI_file
            case 'ini':
                $result = parse_ini_string($string, true);
                if ($result === false) {
                    throw new RuntimeException('Can\'t parse INI front matter.');
                }

                return $result;
            // https://wikipedia.org/wiki/JSON
            case 'json':
                try {
                    $result = json_decode($string, true);
                    if ($result === null && json_last_error() !== JSON_ERROR_NONE) {
                        throw new \Exception('JSON error.');
                    }
                } catch (\Exception $e) {
                    throw new RuntimeException('Can\'t parse JSON front matter.');
                }

                return $result;
            // https://wikipedia.org/wiki/TOML
            case 'toml':
                try {
                    $result = Toml::Parse((string) $string) ?? [];
                    if (!is_array($result)) {
                        throw new RuntimeException('Can\'t parse TOML front matter.');
                    }

                    return $result;
                } catch (TomlParseException $e) {
                    throw new RuntimeException($e->getMessage(), $e->getParsedFile(), $e->getParsedLine());
                } catch (\Exception $e) {
                    throw new RuntimeException($e->getMessage());
                }
            // https://wikipedia.org/wiki/YAML
            case 'yaml':
            default:
                try {
                    $result = Yaml::parse((string) $string) ?? [];
                    if (!is_array($result)) {
                        throw new RuntimeException('Can\'t parse YAML front matter.');
                    }

                    return $result;
                } catch (ParseException $e) {
                    throw new RuntimeException($e->getMessage(), $e->getParsedFile(), $e->getParsedLine());
                } catch (\Exception $e) {
                    throw new RuntimeException($e->getMessage());
                }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function convertBody(string $string): string
    {
        $parsedown = new Parsedown($this->builder);

        return $parsedown->text($string);
    }
}
