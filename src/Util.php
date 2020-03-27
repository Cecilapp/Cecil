<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil;

use Symfony\Component\Filesystem\Filesystem;

class Util
{
    /**
     * Symfony\Component\Filesystem.
     *
     * @var Filesystem
     */
    protected static $fs;

    /**
     * Return Symfony\Component\Filesystem instance.
     *
     * @return Filesystem
     */
    public static function getFS(): Filesystem
    {
        if (!self::$fs instanceof Filesystem) {
            self::$fs = new Filesystem();
        }

        return self::$fs;
    }

    /**
     * Checks if a date is valid.
     *
     * @param string|null $date
     * @param string      $format
     *
     * @return bool
     */
    public static function isDateValid($date, string $format = 'Y-m-d'): bool
    {
        if ($date === null) {
            return false;
        }
        $d = \DateTime::createFromFormat($format, $date);

        return $d && $d->format($format) === $date;
    }

    /**
     * Date to DateTime.
     *
     * @param mixed $date
     *
     * @return \DateTime
     */
    public static function dateToDatetime($date): \DateTime
    {
        // DateTime
        if ($date instanceof \DateTime) {
            return $date;
        }
        // timestamp or AAAA-MM-DD
        if (is_numeric($date)) {
            return (new \DateTime())->setTimestamp($date);
        }
        // string (ie: '01/01/2019', 'today')
        if (is_string($date)) {
            return new \DateTime($date);
        }
    }

    /**
     * Format class name.
     *
     * @param \object $class
     * @param array   $options
     *
     * @return string
     */
    public static function formatClassName($class, array $options = []): string
    {
        $lowercase = false;
        extract($options);

        $className = substr(strrchr(get_class($class), '\\'), 1);
        if ($lowercase) {
            $className = strtolower($className);
        }

        return $className;
    }

    /**
     * Test if a string is an external URL or not.
     *
     * @param string|null $url
     *
     * @return bool
     */
    public static function isExternalUrl($url): bool
    {
        if ($url === null) {
            return false;
        }

        return (bool) preg_match('~^(?:f|ht)tps?://~i', $url);
    }

    /**
     * Test if a remote file exists or not.
     *
     * @param string $remoteFile
     *
     * @return bool
     */
    public static function isUrlFileExists(string $remoteFile): bool
    {
        $handle = @fopen($remoteFile, 'r');
        if (is_resource($handle)) {
            return true;
        }

        return false;
    }

    /**
     * Convert an array of strings into a path.
     *
     * @param string $path
     *
     * @return string
     */
    public static function joinPath(string ...$path): string
    {
        array_walk($path, function (&$value) {
            $value = str_replace('\\', '/', $value);
            $value = rtrim($value, '/');
        });

        return implode('/', $path);
    }

    /**
     * Convert an array of strings into a system path.
     *
     * @param string $path
     *
     * @return string
     */
    public static function joinFile(string ...$path): string
    {
        array_walk($path, function (&$value) {
            $value = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $value);
            $value = rtrim($value, DIRECTORY_SEPARATOR);
        });

        return implode(DIRECTORY_SEPARATOR, $path);
    }

    /**
     * Convert array to string.
     *
     * @param array  $array
     * @param string $separator Separtor between the key and the value in the result string
     *
     * @return string
     */
    public static function arrayToString(array $array, string $separator = ':'): string
    {
        $string = '';

        foreach ($array as $key => $value) {
            $string .= sprintf('%s%s%s, ', $key, $separator, $value);
        }

        return substr($string, 0, -2);
    }

    /**
     * Conbine array to string.
     *
     * @param array  $array
     * @param string $keyToKey   the cuurrent key who become the key of the new array
     * @param string $keyToValue the cuurrent key who become the value of the new array
     * @param string $separator  Separtor between the key and the value in the result string
     *
     * @return string
     */
    public static function CombineArrayToString(array $array, $keyToKey, $keyToValue, string $separator = ':'): string
    {
        $string = '';

        foreach ($array as $subArray) {
            $string .= sprintf('%s%s%s, ', $subArray[$keyToKey], $separator, $subArray[$keyToValue]);
        }

        return substr($string, 0, -2);
    }
}
