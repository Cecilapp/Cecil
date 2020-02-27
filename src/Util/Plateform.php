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
    const OS_UNKNOWN = 1;
    const OS_WIN = 2;
    const OS_LINUX = 3;
    const OS_OSX = 4;

    protected static $pharPath;

    /**
     * Running from Phar or not?
     *
     * @return bool
     */
    public static function isPhar(): bool
    {
        if (!empty(\Phar::running())) {
            self::$pharPath = \Phar::running();

            return true;
        }

        return false;
    }

    /**
     * Returns the full path on disk to the currently executing Phar archive.
     *
     * @return string
     */
    public static function getPharPath(): string
    {
        if (!isset(self::$pharPath)) {
            self::isPhar();
        }

        return self::$pharPath;
    }

    /**
     * Whether the host machine is running a Windows OS.
     *
     * @return bool
     */
    public static function isWindows(): bool
    {
        return defined('PHP_WINDOWS_VERSION_BUILD');
    }

    /**
     * Opens a URL in the system default browser.
     *
     * @param string $url
     *
     * @return void
     */
    public static function openBrowser($url): void
    {
        if (self::isWindows()) {
            passthru('start "web" explorer "'.$url.'"');
            return;
        }
        passthru('which xdg-open', $linux);
        passthru('which open', $osx);
        if (0 === $linux) {
            passthru('xdg-open '.$url);
        } elseif (0 === $osx) {
            passthru('open '.$url);
        }
    }

    /**
     * Search for system OS in PHP_OS constant.
     *
     * @return int
     */
    public static function getOS(): int
    {
        switch (PHP_OS) {
            case 'Unix':
            case 'FreeBSD':
            case 'NetBSD':
            case 'OpenBSD':
            case 'Linux':
                return self::OS_LINUX;
            case 'WINNT':
            case 'WIN32':
            case 'Windows':
            case 'CYGWIN_NT':
                return self::OS_WIN;
            case 'Darwin':
                return self::OS_OSX;
            default:
                return self::OS_UNKNOWN;
        }
    }
}
