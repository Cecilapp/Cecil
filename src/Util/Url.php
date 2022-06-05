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

class Url
{
    /**
     * Tests if a string is an URL.
     */
    public static function isUrl(string $url): bool
    {
        return (bool) preg_match('~^(?:f|ht)tps?://~i', $url);
    }

    /**
     * Tests if a remote file exists.
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
