<?php declare(strict_types=1);

/*
 * This file is part of Cecil.
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
use Cecil\Exception\RuntimeException;
use Cecil\Util;
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

    public function __construct(Builder $builder, string $pool = '')
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
            if (false === $content = Util\File::fileGetContents($this->getFilePathname($key))) {
                return $default;
            }
            $data = unserialize($content);
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
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
        throw new \Exception(\sprintf('%s::%s not yet implemented.', __CLASS__, __FUNCTION__));
    }

    /**
     * {@inheritdoc}
     */
    public function setMultiple($values, $ttl = null)
    {
        throw new \Exception(\sprintf('%s::%s not yet implemented.', __CLASS__, __FUNCTION__));
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple($keys)
    {
        throw new \Exception(\sprintf('%s::%s not yet implemented.', __CLASS__, __FUNCTION__));
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
     * Creates key with the MD5 hash of a string.
     */
    public function createKeyFromString(string $value): string
    {
        return hash('md5', $value);
    }

    /**
     * Creates key from a file: $relativePath + '__' + MD5 hash.
     *
     * @throws RuntimeException
     */
    public function createKeyFromPath(string $path, string $relativePath): string
    {
        if (false === $content = Util\File::fileGetContents($path)) {
            throw new RuntimeException(\sprintf('Can\'t create cache key for "%s"', $path));
        }

        return $this->prepareKey(\sprintf('%s__%s', $relativePath, $this->createKeyFromString($content)));
    }

    /**
     * Creates key from an Asset source: 'filename_ext_$tag' + '__' + MD5 hash.
     */
    public function createKeyFromAsset(Asset $asset, array $tags = null): string
    {
        $tags = implode('_', $tags ?? []);

        return $this->prepareKey(\sprintf('%s%s%s__%s', $asset['filename'], "_{$asset['ext']}", $tags ? "_$tags" : '', $this->createKeyFromString($asset['content_source'] ?? '')));
    }

    /**
     * Returns cache file pathname from key.
     */
    private function getFilePathname(string $key): string
    {
        return Util::joinFile($this->cacheDir, \sprintf('%s.ser', $key));
    }

    /**
     * Removes previous cache files.
     */
    private function prune(string $key): bool
    {
        try {
            $key = $this->prepareKey($key);
            $pattern = Util::joinFile($this->cacheDir, explode('__', $key)[0]).'*';
            foreach (glob($pattern) as $filename) {
                Util\File::getFS()->remove($filename);
            }
        } catch (\Exception $e) {
            $this->builder->getLogger()->error($e->getMessage());

            return false;
        }

        return true;
    }

    /**
     * $key must be a valid string.
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
