<?php
/**
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Assets;

use Cecil\Builder;
use Cecil\Config;
use Cecil\Util;
use Exception;
use Psr\SimpleCache\CacheInterface;

class Cache implements CacheInterface
{
    /** @var Builder */
    protected $builder;
    /** @var Config */
    protected $config;
    /** @var string */
    protected $pool;
    /** @var string */
    protected $rootPath;
    /** @var string */
    protected $cacheDir;

    /**
     * @param Builder $builder
     * @param string  $pool
     * @param string  $rootPath
     */
    public function __construct(Builder $builder, string $pool, string $rootPath)
    {
        $this->builder = $builder;
        $this->config = $builder->getConfig();
        $this->pool = $pool;
        $this->rootPath = $rootPath;
        $this->cacheDir = Util::joinFile($this->config->getCachePath(), $pool);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key, $default = null)
    {
        return file_get_contents($this->getValueFilePathname($key)) ?: $default;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $ttl = null)
    {
        if ($ttl !== null) {
            throw new Exception(sprintf('%s\%s not yet implemented.', __CLASS__, __FUNCTION__));
        }

        // dumps value in a file
        Util::getFS()->dumpFile($this->getValueFilePathname($key), $value);

        // prunes hash files
        $this->pruneHashFiles($key);

        // creates hash file
        Util::getFS()->mkdir(Util::joinFile($this->cacheDir, 'hash'));
        Util::getFS()->touch($this->getHashFilePathname($key, $this->createHash($value)));
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        Util::getFS()->remove($this->getValueFilePathname($key));
        $this->pruneHashFiles($key);
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        Util::getFS()->remove($this->cacheDir);
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple($keys, $default = null)
    {
        throw new Exception(sprintf('%s\%s not yet implemented.', __CLASS__, __FUNCTION__));
    }

    /**
     * {@inheritdoc}
     */
    public function setMultiple($values, $ttl = null)
    {
        throw new Exception(sprintf('%s\%s not yet implemented.', __CLASS__, __FUNCTION__));
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple($keys)
    {
        throw new Exception(sprintf('%s\%s not yet implemented.', __CLASS__, __FUNCTION__));
    }

    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        if ($this->config->get('cache.enabled') === false) {
            return false;
        }

        if (!Util::getFS()->exists($this->getValueFilePathname($key))) {
            return false;
        }

        return true;
    }

    /**
     * Returns true if cache entry exists and hash matches.
     *
     * @param string $key
     * @param string $hash
     *
     * @return bool
     */
    public function hasHash(string $key, string $hash): bool
    {
        if (!$this->has($key) || !Util::getFS()->exists($this->getHashFilePathname($key, $hash))) {
            return false;
        }

        return true;
    }

    /**
     * Creates the hash (MD5) of a value.
     *
     * @param string $value
     *
     * @return string
     */
    private function createHash(string $value): string
    {
        return hash('md5', $value);
    }

    /**
     * Returns value file pathname.
     *
     * @param string $key
     *
     * @return string
     */
    private function getValueFilePathname(string $key): string
    {
        return Util::joinFile(
            $this->cacheDir,
            'files',
            $this->getRelativePathname($key)
        );
    }

    /**
     * Returns hash file pathname.
     *
     * @param string $key
     *
     * @return string
     */
    private function getHashFilePathname(string $key, string $hash): string
    {
        return Util::joinFile(
            $this->cacheDir,
            'hash',
            $this->preparesHashFile($this->getRelativePathname($key)).$hash
        );
    }

    /**
     * Returns relative path from the $rootPath.
     *
     * @param string $key
     *
     * @return string
     */
    private function getRelativePathname(string $key): string
    {
        $relativePath = trim(Util::getFS()->makePathRelative(dirname($key), $this->rootPath), './');

        return Util::joinFile($relativePath, basename($key));
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
     * @param string $key
     *
     * @return void
     */
    private function pruneHashFiles(string $key): void
    {
        $path = $this->getRelativePathname($key);
        $pattern = Util::joinFile($this->cacheDir, 'hash', $this->preparesHashFile($path)).'*';
        foreach (glob($pattern) as $filename) {
            Util::getFS()->remove($filename);
        }
    }
}
