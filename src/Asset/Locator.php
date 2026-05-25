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

namespace Cecil\Asset;

use Cecil\Builder;
use Cecil\Cache;
use Cecil\Collection\Page\Page;
use Cecil\Config;
use Cecil\Exception\RuntimeException;
use Cecil\Util;

/**
 * Locates asset files in the project directory tree.
 *
 * Searches in (in order):
 *   1. Remote URL (fetched and cached)
 *   2. assets/
 *   3. themes/<theme>/assets/
 *   4. static/
 *   5. themes/<theme>/static/
 */
class Locator
{
    /** @var Builder */
    protected $builder;

    /** @var Config */
    protected $config;

    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
        $this->config = $builder->getConfig();
    }

    /**
     * Returns local file path and updated path, or throw an exception.
     * If $fallback path is set, it will be used if the remote file is not found.
     *
     * @return array{file: string, path: string}
     *
     * @throws RuntimeException
     */
    public function locate(string $path, ?string $fallback = null, ?string $userAgent = null, ?string $language = null): array
    {
        // remote file
        if (Util\File::isRemote($path)) {
            try {
                $url = $path;
                $path = Util::joinPath(
                    (string) $this->config->get('assets.target'),
                    self::buildPathFromUrl($url)
                );
                $cache = new Cache($this->builder, 'assets/_remote');
                if (!$cache->has($path)) {
                    $content = $this->getRemoteFileContent($url, $userAgent);
                    $cache->set($path, [
                        'content' => $content,
                        'path'    => $path,
                    ], $this->config->get('cache.assets.remote.ttl'));
                }

                return [
                    'file' => $cache->getContentFile($path),
                    'path' => $path,
                ];
            } catch (RuntimeException $e) {
                if (empty($fallback)) {
                    throw new RuntimeException($e->getMessage());
                }
                $path = $fallback;
            }
        }

        $localizedPath = self::buildLocalizedPath($path, $language);

        // checks in assets/
        if ($result = $this->searchInDirectory($this->config->getAssetsPath(), $path, $localizedPath)) {
            return $result;
        }

        // checks in each themes/<theme>/assets/
        foreach ($this->config->getTheme() ?? [] as $theme) {
            if ($result = $this->searchInDirectory($this->config->getThemeDirPath($theme, 'assets'), $path, $localizedPath)) {
                return $result;
            }
        }

        // checks in static/
        if ($result = $this->searchInDirectory($this->config->getStaticPath(), $path, $localizedPath)) {
            return $result;
        }

        // checks in each themes/<theme>/static/
        foreach ($this->config->getTheme() ?? [] as $theme) {
            if ($result = $this->searchInDirectory($this->config->getThemeDirPath($theme, 'static'), $path, $localizedPath)) {
                return $result;
            }
        }

        throw new RuntimeException(\sprintf('Unable to locate file "%s".', $path));
    }

    /**
     * Builds a relative path from a URL.
     * Used for remote files.
     */
    public static function buildPathFromUrl(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);
        $path = \is_string($path = parse_url($url, PHP_URL_PATH)) && $path !== '/' ? $path : '-index.html';
        $query = parse_url($url, PHP_URL_QUERY);
        $ext = pathinfo($path, \PATHINFO_EXTENSION);
        // Google Fonts hack
        if (Util\Str::endsWith($path, '/css') || Util\Str::endsWith($path, '/css2')) {
            $ext = 'css';
        }

        return Page::slugify(\sprintf('%s%s%s%s', $host, self::sanitize($path), $query ? "-$query" : '', $query && $ext ? ".$ext" : ''));
    }

    /**
     * Replaces some characters by '_'.
     */
    public static function sanitize(string $string): string
    {
        return str_replace(['<', '>', ':', '"', '\\', '|', '?', '*'], '_', $string);
    }

    /**
     * Searches for a file (with optional localized variant) in a directory.
     *
     * @return array{file: string, path: string}|null
     */
    private function searchInDirectory(string $baseDir, string $path, ?string $localizedPath): ?array
    {
        if ($localizedPath !== null) {
            $file = Util::joinFile($baseDir, $localizedPath);
            if (Util\File::getFS()->exists($file)) {
                return ['file' => $file, 'path' => $localizedPath];
            }
        }
        $file = Util::joinFile($baseDir, $path);
        if (Util\File::getFS()->exists($file)) {
            return ['file' => $file, 'path' => $path];
        }

        return null;
    }

    /**
     * Builds a localized variant of a path (e.g. "style.css" → "style.fr.css").
     * Returns null if no localization is needed or possible.
     */
    public static function buildLocalizedPath(string $path, ?string $language = null): ?string
    {
        if ($language === null || $language === '') {
            return null;
        }

        $pathInfo = pathinfo($path);
        if (empty($pathInfo['extension']) || empty($pathInfo['filename'])) {
            return null;
        }

        $filenameParts = explode('.', $pathInfo['filename']);
        if (end($filenameParts) === $language) {
            return null;
        }

        $localizedFilename = \sprintf('%s.%s.%s', $pathInfo['filename'], $language, $pathInfo['extension']);
        if (empty($pathInfo['dirname']) || $pathInfo['dirname'] === '.') {
            return $localizedFilename;
        }

        return Util::joinPath($pathInfo['dirname'], $localizedFilename);
    }

    /**
     * Try to get remote file content.
     * Returns file content or throw an exception.
     *
     * @throws RuntimeException
     */
    private function getRemoteFileContent(string $path, ?string $userAgent = null): string
    {
        $timeout = (int) ($this->config->get('assets.remote.timeout') ?? 30);

        if (!Util\File::isRemoteExists($path)) {
            throw new RuntimeException(\sprintf('Unable to get remote file "%s".', $path));
        }
        if (false === $content = Util\File::fileGetContents($path, $userAgent, $timeout)) {
            throw new RuntimeException(\sprintf('Unable to get content of remote file "%s".', $path));
        }
        if (\strlen($content) <= 1) {
            throw new RuntimeException(\sprintf('Remote file "%s" is empty.', $path));
        }

        return $content;
    }
}
