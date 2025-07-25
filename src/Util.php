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

namespace Cecil;

use Symfony\Component\Filesystem\Path;

/**
 * Utility class.
 *
 * Provides various utility methods for formatting class names, method names,
 * joining paths, converting memory sizes, and more.
 */
class Util
{
    /**
     * Formats a class name.
     *
     * ie: "Cecil\Step\OptimizeHtml" become "OptimizeHtml"
     *
     * @param object $class
     */
    public static function formatClassName($class, array $options = []): string
    {
        $lowercase = false;
        extract($options, EXTR_IF_EXISTS);

        $className = substr(strrchr(\get_class($class), '\\'), 1);
        if ($lowercase) {
            $className = strtolower($className);
        }

        return $className;
    }

    /**
     * Formats a method name.
     *
     * ie: "Cecil\Renderer\Extension\Core::asset()" become "asset()"
     *
     * @param string $method
     */
    public static function formatMethodName(string $method): string
    {
        $methodName = explode('::', $method)[1];

        return $methodName;
    }

    /**
     * Converts an array of strings into a path.
     */
    public static function joinPath(string ...$path): string
    {
        $path = array_filter($path, function ($path) {
            return !empty($path) && !\is_null($path);
        });
        array_walk($path, function (&$value, $key) {
            $value = str_replace('\\', '/', $value);
            $value = rtrim($value, '/');
            $value = $key == 0 ? $value : ltrim($value, '/');
        });

        return Path::canonicalize(implode('/', $path));
    }

    /**
     * Converts an array of strings into a system path.
     */
    public static function joinFile(string ...$path): string
    {
        array_walk($path, function (&$value, $key) use (&$path) {
            $value = str_replace(['\\', '/'], [DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR], $value);
            $value = rtrim($value, DIRECTORY_SEPARATOR);
            $value = $key == 0 ? $value : ltrim($value, DIRECTORY_SEPARATOR);
            // unset entry with empty value
            if (empty($value)) {
                unset($path[$key]);
            }
        });

        return implode(DIRECTORY_SEPARATOR, $path);
    }

    /**
     * Converts memory size for human.
     */
    public static function convertMemory($size): string
    {
        if ($size === 0) {
            return '0';
        }
        $unit = ['b', 'kb', 'mb', 'gb', 'tb', 'pb'];

        return \sprintf('%s %s', round($size / pow(1024, $i = floor(log($size, 1024))), 2), $unit[$i]);
    }

    /**
     * Converts microtime interval for human.
     */
    public static function convertMicrotime(float $start): string
    {
        $time = microtime(true) - $start;
        if ($time < 1) {
            return \sprintf('%s ms', round($time * 1000, 0));
        }

        return \sprintf('%s s', round($time, 2));
    }

    /**
     * Loads class from the source directory, in the given subdirectory $dir.
     */
    public static function autoload(Builder $builder, string $dir): void
    {
        spl_autoload_register(function ($className) use ($builder, $dir) {
            $classFile = Util::joinFile($builder->getConfig()->getSourceDir(), $dir, "$className.php");
            if (is_readable($classFile)) {
                require $classFile;
                return;
            }
            // in themes
            foreach ($builder->getConfig()->getTheme() ?? [] as $theme) {
                $classFile = Util::joinFile($builder->getConfig()->getThemeDirPath($theme, $dir), "$className.php");
                if (is_readable($classFile)) {
                    require $classFile;
                    return;
                }
            }
        });
    }
}
