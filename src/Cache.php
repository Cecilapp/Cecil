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
    /** Reserved characters that cannot be used in a key */
    public const RESERVED_CHARACTERS = '{}()/\@:';

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
                Util\File::getFS()->dumpFile($this->getContentFile($value['path']), $value['content']);
                unset($value['content']);
            }
            // serialize data
            $data = serialize([
                'value'      => $value,
                'expiration' => $ttl === null ? null : time() + $this->duration($ttl),
            ]);
            Util\File::getFS()->dumpFile($this->getFile($key), $data);
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
        if (!Util\File::getFS()->exists($this->getFile($key))) {
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
            if (false === $content = Util\File::fileGetContents($this->getFile($key))) {
                return $default;
            }
            // unserialize data
            $data = unserialize($content);
            // check expiration
            if ($data['expiration'] !== null && $data['expiration'] <= time()) {
                $this->builder->getLogger()->debug(\sprintf('Cache expired: "%s"', $key));
                // remove expired cache file
                $this->delete($key);

                return $default;
            }
            // get content from dedicated file
            if (\is_array($data['value']) && isset($data['value']['path'])) {
                if (false !== $content = Util\File::fileGetContents($this->getContentFile($data['value']['path']))) {
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
            Util\File::getFS()->remove($this->getFile($key));
            $this->prune($key);
            // remove content dedicated file
            $value = $this->get($key);
            if (!empty($value['path'])) {
                $this->deleteContentFile($value['path']);
            }
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
     * Creates key: "$name_$tags__HASH__VERSION".
     *
     * The $name is generated from the $value (string, Asset, or file) and can be customized with the $name parameter.
     * The $tags are generated from the $tags parameter and can be used to add extra information to the key (e.g., options used to process the value). They are optional and can be empty.
     * The $hash is generated from the $value and is used to identify the content. It is generated with a fast non-cryptographic hash function (xxh128) to ensure good performance.
     * The $version is the Cecil version, used to invalidate cache when Cecil is updated.
     * The key is sanitized to remove reserved characters and ensure it is a valid file name. It is also truncated to 200 characters to avoid issues with file system limits.
     *
     * @throws \InvalidArgumentException if the $value type is not supported or if the generated key contains reserved characters.
     */
    public function createKey(mixed $value, ?string $name = null, ?array $tags = null): string
    {
        // string
        if (\is_string($value)) {
            $name .= '-' . hash('adler32', $value);
            $hash = hash('xxh128', $value);
        }

        // asset
        if ($value instanceof Asset) {
            $name = "{$value['_path']}_{$value['ext']}";
            $hash = $value['hash'];
        }

        // file
        if ($value instanceof \Symfony\Component\Finder\SplFileInfo) {
            $name = $value->getRelativePathname();
            $hash = hash_file('xxh128', $value->getRealPath());
        }

        if (empty($name) or empty($hash)) {
            throw new \InvalidArgumentException(\sprintf('Unable to create cache key: invalid value type "%s".', get_debug_type($value)));
        }

        // tags
        $t = $tags;
        $tags = [];
        if ($t !== null) {
            foreach ($t as $key => $value) {
                switch (\gettype($value)) {
                    case 'boolean':
                        if ($value === true) {
                            $tags[] = str_replace('_', '', $key);
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
        if (\count($tags) > 0) {
            $name .= '_' . implode('_', $tags);
        }

        $name = self::sanitizeKey($name);

        return \sprintf('%s__%s__%s', $name, $hash, $this->builder->getVersion());
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
     * Returns cache content file from path.
     */
    public function getContentFile(string $path): string
    {
        $path = str_replace(['https://', 'http://'], '', $path); // remove protocol (if URL)

        return Util::joinFile($this->cacheDir, '_files', $path);
    }

    /**
     * Returns cache file from key.
     */
    private function getFile(string $key): string
    {
        if (\count(explode('-', $key)) > 2) {
            return Util::joinFile($this->cacheDir, explode('-', $key, 2)[0], explode('-', $key, 2)[1]) . '.ser';
        }

        return Util::joinFile($this->cacheDir, "$key.ser");
    }

    /**
     * Prepares and validate $key.
     *
     * @throws \InvalidArgumentException if the $key contains reserved characters.
     */
    private static function sanitizeKey(string $key): string
    {
        $key = str_replace(['https://', 'http://'], '', $key); // remove protocol (if URL)
        $key = Page::slugify($key);                            // slugify
        $key = trim($key, '/');                                // remove leading/trailing slashes
        $key = str_replace(['\\', '/'], ['-', '-'], $key);     // replace slashes by hyphens
        $key = substr($key, 0, 200);                           // truncate to 200 characters (NTFS filename length limit is 255 characters)

        if (false !== strpbrk($key, self::RESERVED_CHARACTERS)) {
            throw new \InvalidArgumentException(\sprintf('Cache key "%s" contains reserved characters "%s".', $key, self::RESERVED_CHARACTERS));
        }

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
            Util\File::getFS()->remove($this->getContentFile($path));
        } catch (\Exception $e) {
            $this->builder->getLogger()->error($e->getMessage());

            return false;
        }

        return true;
    }
}
