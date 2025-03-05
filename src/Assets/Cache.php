<?php

declare(strict_types=1);

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
use Cecil\Exception\RuntimeException;
use Cecil\Util;
use Psr\SimpleCache\CacheInterface;

class Cache implements CacheInterface
{
    /** @var Builder */
    protected $builder;

    /** @var string */
    protected $cacheDir;

    /** @var int */
    protected $duration;

    public function __construct(Builder $builder, string $pool = '')
    {
        $this->builder = $builder;
        $this->cacheDir = Util::joinFile($builder->getConfig()->getCachePath(), $pool);
        $this->duration = 31536000; // 1 year
    }

    /**
     * {@inheritdoc}
     */
    public function get($key, $default = null): mixed
    {
        try {
            $key = $this->prepareKey($key);
            if (false === $content = Util\File::fileGetContents($this->getFilePathname($key))) {
                return $default;
            }
            $data = unserialize($content);

            // check expiration
            if ($data['expiration'] <= time()) {
                $this->delete($key);

                return $default;
            }
        } catch (\Exception $e) {
            $this->builder->getLogger()->error($e->getMessage());

            return $default;
        }

        return $data['value'];
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $ttl = null): bool
    {
        try {
            $key = $this->prepareKey($key);
            $data = serialize([
                'value'      => $value,
                'expiration' => time() + $this->duration($ttl),
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
    public function delete($key): bool
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
    public function clear(): bool
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
    public function getMultiple($keys, $default = null): iterable
    {
        throw new \Exception(\sprintf('%s::%s not yet implemented.', __CLASS__, __FUNCTION__));
    }

    /**
     * {@inheritdoc}
     */
    public function setMultiple($values, $ttl = null): bool
    {
        throw new \Exception(\sprintf('%s::%s not yet implemented.', __CLASS__, __FUNCTION__));
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple($keys): bool
    {
        throw new \Exception(\sprintf('%s::%s not yet implemented.', __CLASS__, __FUNCTION__));
    }

    /**
     * {@inheritdoc}
     */
    public function has($key): bool
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
    public function createKeyFromString(string $value, ?string $suffix = null): string
    {
        return \sprintf('%s%s__%s', hash('md5', $value), ($suffix ? '_' . $suffix : ''), $this->builder->getVersion());
    }

    /**
     * Creates key from a file: "$relativePath__MD5".
     *
     * @throws RuntimeException
     */
    public function createKeyFromPath(string $path, string $relativePath): string
    {
        if (false === $content = Util\File::fileGetContents($path)) {
            throw new RuntimeException(\sprintf('Can\'t create cache key for "%s".', $path));
        }

        return $this->prepareKey(\sprintf('%s__%s', $relativePath, $this->createKeyFromString($content)));
    }

    /**
     * Creates key from an Asset: "$filename_$ext_$tags__VERSION__MD5".
     */
    public function createKeyFromAsset(Asset $asset, ?array $tags = null): string
    {
        $tags = implode('_', $tags ?? []);

        return $this->prepareKey(\sprintf(
            '%s%s%s__%s',
            $asset['filename'],
            "_{$asset['ext']}",
            $tags ? "_$tags" : '',
            $this->createKeyFromString($asset['content_source'] ?? '')
        ));
    }

    /**
     * Clear cache by pattern.
     */
    public function clearByPattern(string $pattern): int
    {
        try {
            if (!Util\File::getFS()->exists($this->cacheDir)) {
                throw new RuntimeException(\sprintf('The cache directory "%s" does not exists.', $this->cacheDir));
            }
            $fileCount = 0;
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->cacheDir),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    if (preg_match('/' . $pattern . '/i', $file->getPathname())) {
                        Util\File::getFS()->remove($file->getPathname());
                        $fileCount++;
                        $this->builder->getLogger()->debug(\sprintf('Cache file "%s" removed', Util\File::getFS()->makePathRelative($file->getPathname(), $this->builder->getConfig()->getCachePath())));
                    }
                }
            }
        } catch (\Exception $e) {
            $this->builder->getLogger()->error($e->getMessage());

            return 0;
        }

        return $fileCount;
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
            $keyAsArray = explode('__', $this->prepareKey($key));
            if (!empty($keyAsArray)) {
                $pattern = Util::joinFile($this->cacheDir, $keyAsArray[0]) . '*';
                foreach (glob($pattern) ?: [] as $filename) {
                    Util\File::getFS()->remove($filename);
                }
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
        $key = substr($key, 0, 200); // Maximum filename length in NTFS?

        return $key;
    }

    /**
     * Convert the various expressions of a TTL value into duration in seconds.
     */
    protected function duration(\DateInterval|int|null $ttl): int
    {
        if ($ttl === null) {
            return $this->duration;
        }
        if (\is_int($ttl)) {
            return $ttl;
        }
        if ($ttl instanceof \DateInterval) {
            return (int)$ttl->format('%s');
        }

        throw new \InvalidArgumentException('TTL values must be one of null, int, \DateInterval');
    }
}
