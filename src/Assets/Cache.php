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
    protected $cacheDir;

    /**
     * @param Builder $builder
     * @param string  $pool
     */
    public function __construct(Builder $builder, string $pool)
    {
        $this->builder = $builder;
        $this->config = $builder->getConfig();
        $this->pool = $pool;
        $this->cacheDir = Util::joinFile($this->config->getCachePath(), $pool);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key, $default = null)
    {
        try {
            $key = $this->prepareKey($key);
            $data = unserialize(Util\File::fileGetContents($this->getFilePathname($key)));
        } catch (Exception $e) {
            $this->builder->getLogger()->error($e->getMessage());

            return $default;
        }

        return $data['value'];
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $ttl = null)
    {
        try {
            $key = $this->prepareKey($key);
            $data = serialize([
                'value'      => $value,
                'expiration' => time() + $ttl,
            ]);

            $this->prune($key);
            Util\File::getFS()->dumpFile($this->getFilePathname($key), $data);
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
            $key = $this->prepareKey($key);
            Util\File::getFS()->remove($this->getFilePathname($key));
            $this->prune($key);
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
            Util\File::getFS()->remove($this->cacheDir);
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
        $key = $this->prepareKey($key);
        if (!Util\File::getFS()->exists($this->getFilePathname($key))) {
            return false;
        }

        return true;
    }

    /**
     * Creates a cache key from a value (MD5 hash).
     *
     * @param string $value
     *
     * @return string
     */
    public function createKeyFromValue(string $value): string
    {
        return hash('md5', $value);
    }

    /**
     * Creates a cache key from a file path (with MD5 hash).
     *
     * @param string $path
     * @param string $relativePath
     *
     * @return string
     */
    public function createKeyFromFile(string $path, string $relativePath): string
    {
        $content = Util\File::fileGetContents($path);
        $key = $this->prepareKey(\sprintf('%s__%s', $relativePath, $content !== false ? $this->createKeyFromValue($content) : ''));

        return $key;
    }

    /**
     * Creates a cache key from an Asset (with MD5 hash).
     *
     * @param Asset $asset
     *
     * @return string
     */
    public function createKeyFromAsset(Asset $asset): string
    {
        $key = $this->prepareKey(\sprintf('%s__%s', $asset['path'], $this->createKeyFromValue($asset['source'] ?? '')));

        return $key;
    }

    /**
     * Returns cache file pathname from key.
     *
     * @param string $key
     *
     * @return string
     */
    private function getFilePathname(string $key): string
    {
        return Util::joinFile($this->cacheDir, $key);
    }

    /**
     * Removes previous cache files.
     *
     * @param string $key
     *
     * @return bool
     */
    private function prune(string $key): bool
    {
        try {
            $key = $this->prepareKey($key);
            $pattern = Util::joinFile($this->cacheDir, explode('__', $key)[0]).'*';
            foreach (glob($pattern) as $filename) {
                Util\File::getFS()->remove($filename);
            }
        } catch (Exception $e) {
            $this->builder->getLogger()->error($e->getMessage());

            return false;
        }

        return true;
    }

    /**
     * $key must be a string.
     *
     * @param string $key
     *
     * @return string
     */
    private function prepareKey(string $key): string
    {
        $key = str_replace(['https://', 'http://'], '', $key);
        $key = Page::slugify($key);
        $key = trim($key, '/');
        $key = str_replace(['\\', '/'], ['-', '-'], $key);

        return $key;
    }
}
