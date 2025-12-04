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

namespace Cecil\Util;

use Cecil\Exception\RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Mime\MimeTypes;

/**
 * File utility class.
 *
 * This class provides various utility methods for file handling,
 * including reading file contents, getting media types and extensions,
 * reading EXIF data, and checking if files are remote.
 */
class File
{
    /** @var Filesystem */
    protected static $fs;

    /**
     * Returns a Symfony\Component\Filesystem instance.
     */
    public static function getFS(): Filesystem
    {
        if (!self::$fs instanceof Filesystem) {
            self::$fs = new Filesystem();
        }

        return self::$fs;
    }

    /**
     * file_get_contents() function with error handler.
     */
    public static function fileGetContents(string $filename, ?string $userAgent = null): string|false
    {
        if (empty($filename)) {
            return false;
        }

        set_error_handler(
            function ($severity, $message, $file, $line) {
                throw new \ErrorException($message, 0, $severity, $file, $line, null);
            }
        );

        try {
            $options = [
                'http' => [
                    'method'          => 'GET',
                    'follow_location' => true,
                ],
            ];
            if (!empty($userAgent)) {
                $options['http']['header'] = "User-Agent: $userAgent";
            }

            return file_get_contents($filename, false, stream_context_create($options));
        } catch (\ErrorException) {
            return false;
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Returns the media type and subtype of a file.
     *
     * ie: ['text', 'text/plain']
     */
    public static function getMediaType(string $filename): array
    {
        try {
            if (false !== $subtype = mime_content_type($filename)) {
                return [explode('/', $subtype)[0], $subtype];
            }
            $mimeTypes = new MimeTypes();
            $subtype = $mimeTypes->guessMimeType($filename);
            if ($subtype === null) {
                throw new RuntimeException('Unable to guess the media type.');
            }

            return [explode('/', $subtype)[0], $subtype];
        } catch (\Exception $e) {
            throw new RuntimeException(\sprintf('Unable to get media type of "%s" (%s).', $filename, $e->getMessage()));
        }
    }

    /**
     * Returns the extension of a file.
     */
    public static function getExtension(string $filename): string
    {
        try {
            $ext = pathinfo($filename, \PATHINFO_EXTENSION);
            if (!empty($ext)) {
                return $ext;
            }
            // guess the extension
            $mimeTypes = new MimeTypes();
            $mimeType = $mimeTypes->guessMimeType($filename);
            if ($mimeType === null) {
                throw new RuntimeException('Unable to guess the media type.');
            }
            $exts = $mimeTypes->getExtensions($mimeType);

            return $exts[0];
        } catch (\Exception $e) {
            throw new RuntimeException(
                \sprintf('Unable to get extension of "%s".', $filename),
                previous: $e,
            );
        }
    }

    /**
     * exif_read_data() function with error handler.
     */
    public static function readExif(string $filename): array
    {
        if (empty($filename)) {
            return [];
        }

        set_error_handler(
            function ($severity, $message, $file, $line) {
                throw new \ErrorException($message, 0, $severity, $file, $line, null);
            }
        );

        try {
            if (!\function_exists('exif_read_data')) {
                throw new \ErrorException('`exif` extension is not available.');
            }
            $exif = exif_read_data($filename, null, true);
            if ($exif === false) {
                return [];
            }

            return $exif;
        } catch (\ErrorException) {
            return [];
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Returns the real path of a relative file path.
     */
    public static function getRealPath(string $path): string
    {
        // if file exists
        $filePath = realpath(\Cecil\Util::joinFile(__DIR__, '/../', $path));
        if ($filePath !== false) {
            return $filePath;
        }
        // if Phar
        if (Platform::isPhar()) {
            return \Cecil\Util::joinPath(Platform::getPharPath(), str_replace('../', '/', $path));
        }

        throw new RuntimeException(\sprintf('Unable to get the real path of file "%s".', $path));
    }

    /**
     * Tests if a file path is remote.
     */
    public static function isRemote(string $path): bool
    {
        return (bool) preg_match('~^(?:f|ht)tps?://~i', $path);
    }

    /**
     * Tests if a remote file exists.
     */
    public static function isRemoteExists(string $path): bool
    {
        if (self::isRemote($path)) {
            $handle = @fopen($path, 'r');
            if (!empty($http_response_header)) {
                if (400 < (int) explode(' ', $http_response_header[0])[1]) {
                    return false;
                }
            }
            if (\is_resource($handle)) {
                return true;
            }
        }

        return false;
    }
}
