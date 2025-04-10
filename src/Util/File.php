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

namespace Cecil\Util;

use Cecil\Exception\RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Mime\MimeTypes;

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
     *
     * @return string|false
     */
    public static function fileGetContents(string $filename, bool $userAgent = false)
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
            if ($userAgent) {
                $options = [
                    'http' => [
                        'method'          => 'GET',
                        'header'          => 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.47 Safari/537.36',
                        'follow_location' => true,
                    ],
                ];

                return file_get_contents($filename, false, stream_context_create($options));
            }

            return file_get_contents($filename);
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
            $mimeTypes = new MimeTypes();
            $mimeType = $mimeTypes->guessMimeType($filename);
        } catch (\Exception $e) {
            throw new RuntimeException(\sprintf('Can\'t get media type of "%s" (%s).', $filename, $e->getMessage()));
        }
        $type = explode('/', $mimeType)[0];

        return [
            $type,     // type
            $mimeType, // subtype
        ];
    }

    /**
     * Returns the extension of a file.
     */
    public static function getExtension(string $filename): string
    {
        try {
            $mimeTypes = new MimeTypes();
            $mimeType = $mimeTypes->guessMimeType($filename);
            $exts = $mimeTypes->getExtensions($mimeType);
        } catch (\Exception $e) {
            throw new RuntimeException(\sprintf('Can\'t get extension of "%s" (%s).', $filename, $e->getMessage()));
        }

        return $exts[0];
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

        throw new RuntimeException(\sprintf('Can\'t get the real path of file "%s".', $path));
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
            if (\is_resource($handle)) {
                return true;
            }
        }

        return false;
    }
}
