<?php
/**
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Cache;

use Cecil\Builder;
use Cecil\Config;
use Cecil\Util;

class Cache
{
    /** @var Builder */
    protected $builder;
    /** @var Config */
    protected $config;
    /** @var string */
    protected $scope;
    /** @var string */
    protected $rootPath;

    /**
     * @param Builder $builder
     * @param string  $scope
     * @param string  $rootPath
     */
    public function __construct(Builder $builder, string $scope, string $rootPath)
    {
        $this->builder = $builder;
        $this->config = $builder->getConfig();

        $this->scope = $scope;
        $this->rootPath = $rootPath;

        if ($this->config->get('cache.enabled') === false) {
            $cacheDir = Util::joinFile($this->config->getCachePath(), $this->scope);
            if (!empty($cacheDir) && is_dir($cacheDir)) {
                Util::getFS()->remove($cacheDir);
            }
            return;
        }
    }

    /**
     * Returns true if cache entry already exists.
     *
     * @param string $file
     *
     * @return bool
     */
    public function isExists(string $file): bool
    {
        if ($this->config->get('cache.enabled') === false) {
            return false;
        }

        if (!Util::getFS()->exists($this->getCachePathname($file)) || !Util::getFS()->exists($this->getHashFilePathname($file))) {
            return false;
        }

        return true;
    }

    /**
     * Returns MD5 hash of $file.
     *
     * @param string $file
     *
     * @return string
     */
    public function getHash(string $file): string
    {
        return hash_file('md5', $file);
    }

    /**
     * Copying cached file and creates hash file.
     *
     * @param string      $file
     * @param string|null $hash
     *
     * @return void
     */
    public function save(string $file, string $hash = null): void
    {
        if ($this->config->get('cache.enabled') === false) {
            return;
        }

        $this->removesOldHashFiles($this->getRelativePathname($file));
        // copy file
        Util::getFS()->copy($file, $this->getCachePathname($file), true);
        // creates hash file
        Util::getFS()->mkdir(Util::joinFile($this->config->getCachePath(), Util::joinFile($this->scope, 'hash')));
        Util::getFS()->touch($this->getHashFilePathname($file, $hash));
    }

    /**
     * Returns path to the from from the $rootPath.
     *
     * @param string $file
     *
     * @return string
     */
    private function getRelativePathname(string $file): string
    {
        $this->relativePath = trim(Util::getFS()->makePathRelative(dirname($file), $this->rootPath), './');

        return Util::joinFile($this->relativePath, basename($file));
    }

    /**
     * Creates cached file path.
     *
     * @param string $file
     *
     * @return string
     */
    private function getCachePathname($file): string
    {
        return Util::joinFile(
            $this->config->getCachePath(),
            Util::joinFile($this->scope, 'files'),
            $this->getRelativePathname($file)
        );
    }

    /**
     * Creates file hash.
     *
     * @param string      $file
     * @param string|null $hash
     *
     * @return string
     */
    private function getHashFilePathname(string $file, string $hash = null): string
    {
        if ($hash === null) {
            $hash = $this->getHash($file);
        }

        return Util::joinFile(
            $this->config->getCachePath(),
            Util::joinFile($this->scope, 'hash'),
            $this->preparesHashFile($this->getRelativePathname($file)).$hash
        );
    }

    /**
     * Prepares hash file path.
     *
     * @param string $path
     *
     * @return string
     */
    private function preparesHashFile(string $path): string
    {
        return str_replace(DIRECTORY_SEPARATOR, '-', $path).'_';
    }

    /**
     * Removes previous hash files.
     *
     * @param string $path
     *
     * @return void
     */
    private function removesOldHashFiles(string $path): void
    {
        $pattern = Util::joinFile($this->config->getCachePath(), Util::joinFile($this->scope, 'hash'), $this->preparesHashFile($path)).'*';
        foreach (glob($pattern) as $filename) {
            Util::getFS()->remove($filename);
        }
    }
}
