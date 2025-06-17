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

/**
 * Platform utility class.
 *
 * This class provides methods to detect the platform (OS) on which the application is running,
 * check if it is running from a Phar archive, and open URLs in the system's default browser.
 */
class Platform
{
    public const OS_UNKNOWN = 1;
    public const OS_WIN = 2;
    public const OS_LINUX = 3;
    public const OS_OSX = 4;

    /** @var string */
    protected static $pharPath;

    /**
     * Running from Phar or not?
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
     */
    public static function getPharPath(): string
    {
        if (!isset(self::$pharPath)) {
            if (!self::isPhar()) {
                throw new \Exception('Can\'t get Phar path.');
            }
        }

        return self::$pharPath;
    }

    /**
     * Whether the host machine is running a Windows OS.
     */
    public static function isWindows(): bool
    {
        return \defined('PHP_WINDOWS_VERSION_BUILD');
    }

    /**
     * Opens a URL in the system default browser.
     */
    public static function openBrowser(string $url): void
    {
        if (self::isWindows()) {
            passthru('start "web" explorer "' . $url . '"');

            return;
        }
        passthru('which xdg-open', $linux);
        passthru('which open', $osx);
        if (0 === $linux) {
            passthru('xdg-open ' . $url);
        } elseif (0 === $osx) {
            passthru('open ' . $url);
        }
    }

    /**
     * Search for system OS in PHP_OS constant.
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
