<?php
/*
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Util;

class Plateform
{
    protected static $pharPath;

    /**
     * Running from Phar or not?
     *
     * @return bool
     */
    public static function isPhar()
    {
        if (!empty(\Phar::running())) {
            self::$pharPath = \Phar::running();

            return true;
        }

        return false;
    }

    /**
     * Returns the full path on disk to the currently executing Phar archive.
     */
    public static function getPharPath()
    {
        if (!isset(self::$pharPath)) {
            self::isPhar();
        }

        return self::$pharPath;
    }

    /**
     * @return bool Whether the host machine is running a Windows OS
     */
    public static function isWindows()
    {
        return defined('PHP_WINDOWS_VERSION_BUILD');
    }

    /**
     * Opens a URL in the system default browser.
     *
     * @param string $url
     */
    public static function openBrowser($url)
    {
        if (self::isWindows()) {
            passthru('start "web" explorer "'.$url.'"');
        } else {
            passthru('which xdg-open', $linux);
            passthru('which open', $osx);
            if (0 === $linux) {
                passthru('xdg-open '.$url);
            } elseif (0 === $osx) {
                passthru('open '.$url);
            }
        }
    }
}
