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

use Cecil\Exception\ConfigException;
use Cecil\Util;
use Dflydev\DotAccessData\Data;
use Dflydev\DotAccessData\Exception\DataException;
use Symfony\Component\Yaml\Yaml;

/**
 * Deprecated configuration options.
 *
 * Checks a configuration against the list of deprecated options (`config/deprecated.php`).
 */
final class Deprecations
{
    /**
     * Checks a configuration for deprecated options, and reports all of them at once.
     * Keys of `$config` can be nested arrays or dot notation paths (ie: environment variables).
     *
     * @param array<string, mixed> $config
     * @param bool                 $env    Options are environment variables (ie: `CECIL_PAGES_DIR`)
     *
     * @throws ConfigException
     */
    public static function check(array $config, ?string $source = null, bool $env = false): void
    {
        if ($config === []) {
            return;
        }
        $data = new Data();
        foreach ($config as $key => $value) {
            $data->set((string) $key, $value);
        }
        $errors = [];
        $deprecated = require Util\File::getRealPath('../config/deprecated.php');
        foreach ($deprecated as $from => $to) {
            try {
                if (!$data->has($from)) {
                    continue;
                }
            } catch (DataException) {
                continue;
            }
            $errors[] = $env ? self::deprecatedVariableMessage($from, $to) : self::deprecatedOptionMessage($from, $to, $data->get($from));
        }
        if ($errors !== []) {
            throw new ConfigException(\sprintf(
                "Deprecated configuration option(s)%s:\n%s",
                $source !== null ? " in $source" : '',
                implode("\n", $errors)
            ));
        }
    }

    /**
     * Returns the message of a deprecated option, with the new configuration snippet built from the current value.
     */
    private static function deprecatedOptionMessage(string $from, string $to, mixed $value): string
    {
        if ($to === '') {
            return "- Option `$from` is deprecated and must be removed.";
        }
        foreach (array_reverse(explode('.', $to)) as $fragment) {
            $value = [$fragment => $value];
        }
        $yaml = rtrim(Yaml::dump($value, 10, 2));

        return "- Option `$from` must be moved to:\n  ```\n  " . str_replace("\n", "\n  ", $yaml) . "\n  ```";
    }

    /**
     * Returns the message of a deprecated environment variable (ie: `CECIL_PAGES_FRONTMATTER_FORMAT`).
     */
    private static function deprecatedVariableMessage(string $from, string $to): string
    {
        $variable = fn (string $key) => 'CECIL_' . strtoupper(str_replace('.', '_', $key));

        return $to === ''
            ? \sprintf('- Variable `%s` is deprecated and must be removed.', $variable($from))
            : \sprintf('- Variable `%s` must be renamed to `%s`.', $variable($from), $variable($to));
    }
}
