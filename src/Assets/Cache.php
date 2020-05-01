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
use Cecil\Collection\Page\Page;
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
        $key = $this->cleanKey($key);
        if (Util::getFS()->exists($this->getValueFilePathname($key))) {
            return file_get_contents($this->getValueFilePathname($key)) ?: $default;
        }

        return $default;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $ttl = null)
    {
        if ($ttl !== null) {
            throw new Exception(sprintf('%s::%s(%s) not yet implemented.', __CLASS__, __FUNCTION__, 'ttl'));
        }

        try {
            $key = $this->cleanKey($key);
            Util::getFS()->dumpFile($this->getValueFilePathname($key), $value);
            $this->pruneHashFiles($key);
            Util::getFS()->mkdir(Util::joinFile($this->cacheDir, 'hash'));
            Util::getFS()->touch($this->getHashFilePathname($key, $this->createHash($value)));
        } catch (Exception $e) {
            $this->builder->getLogger()->error($e->getMessage());

            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        try {
            $key = $this->cleanKey($key);
            Util::getFS()->remove($this->getValueFilePathname($key));
            $this->pruneHashFiles($key);
        } catch (Exception $e) {
            $this->builder->getLogger()->error($e->getMessage());

            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        try {
            Util::getFS()->remove($this->cacheDir);
        } catch (Exception $e) {
            $this->builder->getLogger()->error($e->getMessage());

            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple($keys, $default = null)
    {
        throw new Exception(sprintf('%s::%s not yet implemented.', __CLASS__, __FUNCTION__));
    }

    /**
     * {@inheritdoc}
     */
    public function setMultiple($values, $ttl = null)
    {
        throw new Exception(sprintf('%s::%s not yet implemented.', __CLASS__, __FUNCTION__));
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple($keys)
    {
        throw new Exception(sprintf('%s::%s not yet implemented.', __CLASS__, __FUNCTION__));
    }

    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        if ($this->config->get('cache.enabled') === false) {
            return false;
        }

        $key = $this->cleanKey($key);
        if (!Util::getFS()->exists($this->getValueFilePathname($key))) {
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
    public function createHash(string $value): string
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
    protected function getValueFilePathname(string $key): string
    {
        return Util::joinFile(
            $this->cacheDir,
            'files',
            $key
        );
    }

    /**
     * Returns hash file pathname.
     *
     * @param string $key
     * @param string $hash
     *
     * @return string
     */
    protected function getHashFilePathname(string $key, string $hash): string
    {
        return Util::joinFile(
            $this->cacheDir,
            'hash',
            $this->preparesHashFile($key).trim($hash)
        );
    }

    /**
     * Prepares hash file.
     *
     * @param string $key
     *
     * @return string
     */
    private function preparesHashFile(string $key): string
    {
        return str_replace(['\\', '/'], ['-', '-'], $key).'_';
    }

    /**
     * Removes previous hash files.
     *
     * @param string $key
     *
     * @return bool
     */
    private function pruneHashFiles(string $key): bool
    {
        try {
            $pattern = Util::joinFile($this->cacheDir, 'hash', $this->preparesHashFile($key)).'*';
            foreach (glob($pattern) as $filename) {
                Util::getFS()->remove($filename);
            }
        } catch (Exception $e) {
            $this->builder->getLogger()->error($e->getMessage());

            return false;
        }

        return true;
    }

    /**
     * $key must be a slug.
     *
     * @param string $key
     *
     * @return string
     */
    private function cleanKey(string $key): string
    {
        $key = str_replace(['https://', 'http://'], '', $key);
        $key = Page::slugify($key);

        return $key;
    }
}
