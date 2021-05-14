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

class Url
{
    /**
     * Tests if a string is an URL.
     *
     * @param string $url
     *
     * @return bool
     */
    public static function isUrl(string $url): bool
    {
        return (bool) preg_match('~^(?:f|ht)tps?://~i', $url);
    }

    /**
     * Tests if a remote file exists.
     *
     * @param string $remoteFile
     *
     * @return bool
     */
    public static function isRemoteFileExists(string $remoteFile): bool
    {
        $handle = @fopen($remoteFile, 'r');
        if (is_resource($handle)) {
            return true;
        }

        return false;
    }
}
