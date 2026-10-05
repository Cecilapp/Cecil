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
    public const int OS_UNKNOWN = 1;
    public const int OS_WIN = 2;
    public const int OS_LINUX = 3;
    public const int OS_OSX = 4;

    /** PHP_OS values by OS family */
    private const array OS_FAMILIES = [
        'Unix'      => self::OS_LINUX,
        'FreeBSD'   => self::OS_LINUX,
        'NetBSD'    => self::OS_LINUX,
        'OpenBSD'   => self::OS_LINUX,
        'Linux'     => self::OS_LINUX,
        'WINNT'     => self::OS_WIN,
        'WIN32'     => self::OS_WIN,
        'Windows'   => self::OS_WIN,
        'CYGWIN_NT' => self::OS_WIN,
        'Darwin'    => self::OS_OSX,
    ];

    /** @var string|null */
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
            self::isPhar();
        }

        return self::$pharPath ?? throw new \Exception('Unable to get Phar path.');
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
        // @codeCoverageIgnoreStart
        if (null !== $command = self::getOpenBrowserCommand($url)) {
            passthru($command);
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * Returns the command used to open a URL in the system default browser, or null if none is available.
     */
    public static function getOpenBrowserCommand(string $url): ?string
    {
        if (self::isWindows()) {
            return 'start "web" explorer "' . $url . '"';
        }
        foreach (['xdg-open', 'open'] as $opener) {
            if (self::commandExists($opener)) {
                return $opener . ' ' . $url;
            }
        }

        return null;
    }

    /**
     * Search for system OS in PHP_OS constant.
     */
    public static function getOS(?string $os = null): int
    {
        return self::OS_FAMILIES[$os ?? PHP_OS] ?? self::OS_UNKNOWN;
    }

    /**
     * Whether a command is available in the PATH (Unix-like systems only).
     */
    private static function commandExists(string $command): bool
    {
        exec('command -v ' . escapeshellarg($command) . ' 2>/dev/null', result_code: $code);

        return $code === 0;
    }
}
