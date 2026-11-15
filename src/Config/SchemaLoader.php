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

namespace Cecil\Config;

use Cecil\Collection\Page\Schema;
use Cecil\Exception\ConfigException;
use Cecil\Util;
use Nette\Schema\Expect;
use Nette\Schema\Message;
use Nette\Schema\Processor;
use Nette\Schema\ValidationException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Front matter schemas loader.
 *
 * Loads the front matter schemas of a `schemas/` directory: one YAML file per key of `pages.schema`
 * (a section, or a page as returned by `Schema::pageKey()`), whose path relative to the directory,
 * without its extension, is the key (`schemas/blog.yml` is `blog`, `schemas/blog/index.yml` is `blog/index`).
 * The schema of all pages (`*`) is the `_all.yml` file.
 * A file holds the fields of the schema directly.
 *
 * Schemas layers are merged field by field: the definition of a field is replaced as a whole.
 */
final class SchemaLoader
{
    /**
     * Name of the schemas directory, in the site and in the themes.
     */
    public const string DIR = 'schemas';

    /**
     * Name of the file (without extension) of the schema of all pages (`*`).
     */
    public const string ALL = '_all';

    /**
     * Extensions of the schema files.
     */
    public const array EXTENSIONS = ['yml', 'yaml'];

    /**
     * Loads the schema files of a directory, indexed by key.
     *
     * @param string $dir  Path of the schemas directory
     * @param string $name Name of the directory in error messages (ie: `schemas`, `themes/<theme>/schemas`)
     *
     * @return array<string, array<string, mixed>>
     *
     * @throws ConfigException
     */
    public static function load(string $dir, string $name = self::DIR): array
    {
        if (!is_dir($dir)) {
            return [];
        }
        $finder = Finder::create()
            ->files()
            ->in($dir)
            ->name(array_map(fn (string $ext): string => "*.$ext", self::EXTENSIONS))
            ->sortByName();
        $schemas = [];
        $files = [];
        foreach ($finder as $file) {
            $relative = str_replace('\\', '/', $file->getRelativePathname());
            $path = "$name/$relative";
            $key = self::key($relative);
            if (isset($files[$key])) {
                throw new ConfigException(\sprintf('Schema files "%s" and "%s" have the same key "%s": keep only one of them.', $files[$key], $path, $key));
            }
            $files[$key] = $path;
            $schemas[$key] = self::parse($file->getPathname(), $path);
        }

        return $schemas;
    }

    /**
     * Merges schemas layers, from the lowest to the highest priority:
     * for a same key, fields are merged field by field, the definition of a field being replaced as a whole.
     *
     * @param array<mixed> ...$layers Schemas indexed by key
     *
     * @return array<string, mixed>
     */
    public static function merge(array ...$layers): array
    {
        $schemas = [];
        foreach ($layers as $layer) {
            foreach ($layer as $key => $fields) {
                $schemas[$key] = \is_array($fields) && \is_array($schemas[$key] ?? null) ? array_replace($schemas[$key], $fields) : $fields;
            }
        }

        return $schemas;
    }

    /**
     * Returns the key of a schema file from its path relative to the schemas directory.
     */
    private static function key(string $relative): string
    {
        $key = substr($relative, 0, (int) strrpos($relative, '.'));

        return $key === self::ALL ? Schema::ALL : $key;
    }

    /**
     * Parses and validates a schema file.
     *
     * @param string $file Path of the file
     * @param string $path Path of the file in error messages
     *
     * @return array<string, mixed>
     *
     * @throws ConfigException
     */
    private static function parse(string $file, string $path): array
    {
        if (false === $content = Util\File::fileGetContents($file)) {
            throw new ConfigException(\sprintf('Unable to read schema file "%s".', $path));
        }
        try {
            $fields = Yaml::parse($content, Yaml::PARSE_DATETIME) ?? [];
        } catch (ParseException $e) {
            throw new ConfigException(\sprintf('Schema file "%s" parsing error: %s', $path, $e->getMessage()), previous: $e);
        }
        try {
            (new Processor())->process(Expect::arrayOf(Schema::definition(), Expect::string()), $fields);
        } catch (ValidationException $e) {
            throw new ConfigException(self::errorMessage($path, $e), previous: $e);
        }

        return (array) $fields;
    }

    /**
     * Returns the message of a schema file validation error, with the path of each field in the file.
     */
    private static function errorMessage(string $path, ValidationException $e): string
    {
        $messages = [];
        foreach ($e->getMessageObjects() as $message) {
            if ($message->code === Message::FailedAssertion) {
                $messages[] = \sprintf("Invalid '%s': %s", implode("\u{a0}›\u{a0}", $message->path), $message->variables['assertion'] ?? '');
                continue;
            }
            $messages[] = $message->toString();
        }

        return \sprintf("Invalid schema file \"%s\":\n%s", $path, implode("\n", $messages));
    }
}
