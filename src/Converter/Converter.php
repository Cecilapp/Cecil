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
        if (!\in_array($format, ['yaml', 'ini', 'toml', 'json'])) {
            throw new RuntimeException(sprintf('The front matter format "%s" is not supported ("yaml", "ini", "toml" or "json").', $format));
        }
        $method = sprintf('convert%sToArray', ucfirst($format));

        return self::$method($string);
    }

    /**
     * {@inheritdoc}
     */
    public function convertBody(string $string): string
    {
        $parsedown = new Parsedown($this->builder);

        return $parsedown->text($string);
    }

    /**
     * Converts YAML string to array.
     *
     * @see https://wikipedia.org/wiki/YAML
     */
    private static function convertYamlToArray(string $string): array
    {
        try {
            $result = Yaml::parse((string) $string, Yaml::PARSE_DATETIME) ?? [];
            if (!\is_array($result)) {
                throw new RuntimeException('Can\'t parse YAML front matter.');
            }

            return $result;
        } catch (ParseException $e) {
            throw new RuntimeException($e->getMessage(), null, $e->getParsedLine());
        } catch (\Exception $e) {
            throw new RuntimeException($e->getMessage());
        }
    }

    /**
     * Converts INI string to array.
     *
     * @see https://wikipedia.org/wiki/INI_file
     */
    private static function convertIniToArray(string $string): array
    {
        $result = parse_ini_string($string, true);
        if ($result === false) {
            throw new RuntimeException('Can\'t parse INI front matter.');
        }

        return $result;
    }

    /**
     * Converts TOML string to array.
     *
     * @see https://wikipedia.org/wiki/TOML
     */
    private static function convertTomlToArray(string $string): array
    {
        try {
            $result = Toml::Parse((string) $string) ?? [];
            if (!\is_array($result)) {
                throw new RuntimeException('Can\'t parse TOML front matter.');
            }

            return $result;
        } catch (TomlParseException $e) {
            throw new RuntimeException($e->getMessage(), $e->getParsedFile(), $e->getParsedLine());
        } catch (\Exception $e) {
            throw new RuntimeException($e->getMessage());
        }
    }

    /**
     * Converts JSON string to array.
     *
     * @see https://wikipedia.org/wiki/JSON
     */
    private static function convertJsonToArray(string $string): array
    {
        try {
            $result = json_decode($string, true);
            if ($result === null && json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('JSON error.');
            }

            return $result;
        } catch (\Exception) {
            throw new RuntimeException('Can\'t parse JSON front matter.');
        }
    }
}
