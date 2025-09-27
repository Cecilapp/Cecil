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

namespace Cecil;

use Cecil\Builder;
use Cecil\Collection\Page\Page;
use Cecil\Exception\RuntimeException;
use Cecil\Util;
use Psr\SimpleCache\CacheInterface;

/**
 * Cache class.
 *
 * Provides methods to manage cache files for assets, pages, and other data.
 */
class Cache implements CacheInterface
{
    /** @var Builder */
    protected $builder;

    /** @var string */
    protected $cacheDir;

    public function __construct(Builder $builder, string $pool = '')
    {
        $this->builder = $builder;
        $this->cacheDir = Util::joinFile($builder->getConfig()->getCachePath(), $pool);
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $ttl = null): bool
    {
        try {
            $key = self::sanitizeKey($key);
            $this->prune($key);
            // put file content in a dedicated file
            if (\is_array($value) && !empty($value['content']) && !empty($value['path'])) {
                Util\File::getFS()->dumpFile($this->getContentFilePathname($value['path']), $value['content']);
                unset($value['content']);
            }
            // serialize data
            $data = serialize([
                'value'      => $value,
                'expiration' => $ttl === null ? null : time() + $this->duration($ttl),
            ]);
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
    public function has($key): bool
    {
        $key = self::sanitizeKey($key);
        if (!Util\File::getFS()->exists($this->getFilePathname($key))) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function get($key, $default = null): mixed
    {
        try {
            $key = self::sanitizeKey($key);
            // return default value if file doesn't exists
            if (false === $content = Util\File::fileGetContents($this->getFilePathname($key))) {
                return $default;
            }
            // unserialize data
            $data = unserialize($content);
            // check expiration
            if ($data['expiration'] !== null && $data['expiration'] <= time()) {
                $this->builder->getLogger()->debug(\sprintf('Cache expired: "%s"', $key));
                // remove expired cache
                if ($this->delete($key)) {
                    // remove content file if exists
                    if (!empty($data['value']['path'])) {
                        $this->deleteContentFile($data['value']['path']);
                    }
                }

                return $default;
            }
            // get content from dedicated file
            if (\is_array($data['value']) && isset($data['value']['path'])) {
                if (false !== $content = Util\File::fileGetContents($this->getContentFilePathname($data['value']['path']))) {
                    $data['value']['content'] = $content;
                }
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
    public function delete($key): bool
    {
        try {
            $key = self::sanitizeKey($key);
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
     * Creates key from a name and a hash: "$name__HASH__VERSION".
     */
    public function createKey(string $name, string $hash): string
    {
        $name = self::sanitizeKey($name);

        return \sprintf('%s__%s__%s', $name, $hash, $this->builder->getVersion());
    }

    /**
     * Creates key from a string: "$name__HASH__VERSION".
     * $name is optional to add a human readable name to the key.
     */
    public function createKeyFromValue(?string $name, string $value): string
    {
        $hash = hash('md5', $value);
        $name = $name ?? $hash;

        return $this->createKey($name, $hash);
    }

    /**
     * Creates key from an Asset: "$path_$ext_$tags__HASH__VERSION".
     */
    public function createKeyFromAsset(Asset $asset, ?array $tags = null): string
    {
        $t = $tags;
        $tags = [];

        if ($t !== null) {
            foreach ($t as $key => $value) {
                switch (\gettype($value)) {
                    case 'boolean':
                        if ($value === true) {
                            $tags[] = $key;
                        }
                        break;
                    case 'string':
                    case 'integer':
                        if (!empty($value)) {
                            $tags[] = substr($key, 0, 1) . $value;
                        }
                        break;
                }
            }
        }

        $tagsInline = implode('_', str_replace('_', '', $tags));
        $name = "{$asset['_path']}_{$asset['ext']}_$tagsInline";

        // backward compatibility
        if (empty($asset['hash']) or \is_null($asset['hash'])) {
            throw new RuntimeException(\sprintf('Asset "%s" has no hash. Please clear cache and retry.', $name));
        }

        return $this->createKey($name, $asset['hash']);
    }

    /**
     * Creates key from a file: "RelativePathname__MD5".
     *
     * @throws RuntimeException
     */
    public function createKeyFromFile(\Symfony\Component\Finder\SplFileInfo $file): string
    {
        if (false === $content = Util\File::fileGetContents($file->getRealPath())) {
            throw new RuntimeException(\sprintf('Can\'t create cache key for "%s".', $file));
        }

        return $this->createKeyFromValue($file->getRelativePathname(), $content);
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
                        $this->builder->getLogger()->debug(\sprintf('Cache removed: "%s"', trim(Util\File::getFS()->makePathRelative($file->getPathname(), $this->builder->getConfig()->getCachePath()), '/')));
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
     * Returns cache content file pathname from path.
     */
    public function getContentFilePathname(string $path): string
    {
        $path = str_replace(['https://', 'http://'], '', $path); // remove protocol (if URL)

        return Util::joinFile($this->cacheDir, 'files', $path);
    }

    /**
     * Returns cache file pathname from key.
     */
    private function getFilePathname(string $key): string
    {
        return Util::joinFile($this->cacheDir, "$key.ser");
    }

    /**
     * Prepares and validate $key.
     */
    public static function sanitizeKey(string $key): string
    {
        $key = str_replace(['https://', 'http://'], '', $key); // remove protocol (if URL)
        $key = Page::slugify($key);                            // slugify
        $key = trim($key, '/');                                // remove leading/trailing slashes
        $key = str_replace(['\\', '/'], ['-', '-'], $key);     // replace slashes by hyphens
        $key = substr($key, 0, 200);                           // truncate to 200 characters (NTFS filename length limit is 255 characters)

        return $key;
    }

    /**
     * Removes previous cache files.
     */
    private function prune(string $key): bool
    {
        try {
            $keyAsArray = explode('__', self::sanitizeKey($key));
            // if 2 or more parts (with hash), remove all files with the same first part
            // pattern: `name__hash__version`
            if (!empty($keyAsArray[0]) && \count($keyAsArray) >= 2) {
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
     * Convert the various expressions of a TTL value into duration in seconds.
     */
    protected function duration(int|\DateInterval $ttl): int
    {
        if (\is_int($ttl)) {
            return $ttl;
        }
        if ($ttl instanceof \DateInterval) {
            return (int) $ttl->d * 86400 + $ttl->h * 3600 + $ttl->i * 60 + $ttl->s;
        }

        throw new \InvalidArgumentException('TTL values must be int or \DateInterval');
    }

    /**
     * Removes the cache content file.
     */
    protected function deleteContentFile(string $path): bool
    {
        try {
            Util\File::getFS()->remove($this->getContentFilePathname($path));
        } catch (\Exception $e) {
            $this->builder->getLogger()->error($e->getMessage());

            return false;
        }

        return true;
    }
}
