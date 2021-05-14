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

class Url
{
    /**
     * Tests if data is a valid URL.
     *
     * @param mixed $data
     *
     * @return bool
     */
    public static function isUrl($data): bool
    {
        //return (bool) preg_match('~^(?:f|ht)tps?://~i', $data);
        return (bool) filter_var($data, FILTER_VALIDATE_URL);
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
