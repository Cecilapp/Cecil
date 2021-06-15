<?php
/**
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Util;

use Symfony\Component\Filesystem\Filesystem;

class File
{
    /** @var Filesystem */
    protected static $fs;

    /**
     * Returns a Symfony\Component\Filesystem instance.
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
     * file_get_contents() function with error handler.
     *
     * @param string $filename
     * @param bool   $userAgent
     *
     * @return string|false
     */
    public static function fileGetContents($filename, $userAgent = false)
    {
        set_error_handler(
            function ($severity, $message, $file, $line) {
                throw new \ErrorException($message, 0, $severity, $file, $line, null);
            }
        );
        try {
            if ($userAgent) {
                $options = [
                    'http' => [
                        'method' => 'GET',
                        'header' => 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.47 Safari/537.36',
                        'follow_location' => true,
                    ]
                ];
                $context = stream_context_create($options);

                return file_get_contents($filename, false, stream_context_create($options));
            }

            return file_get_contents($filename);
        } catch (\Exception $e) {
            return false;
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Returns MIME content type and subtype of a file.
     *
     * ie: ['text', 'text/plain']
     *
     * @param string $filename
     *
     * @return string[]
     */
    public static function getMimeType(string $filename): array
    {
        if (false === $subtype = mime_content_type($filename)) {
            throw new \Exception(sprintf('Can\'t get MIME content type of "%s"', $filename));
        }
        $type = explode('/', $subtype)[0];

        return [
            $type,
            $subtype,
        ];
    }
}
