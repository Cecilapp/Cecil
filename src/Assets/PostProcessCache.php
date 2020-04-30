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

use Cecil\Util;
use Exception;
use Psr\SimpleCache\CacheInterface;

class PostProcessCache extends Cache implements CacheInterface
{
    /**
     * Cache::set() with a forced hash.
     *
     * @param string                 $key
     * @param mixed                  $value
     * @param null|int|\DateInterval $ttl
     * @param string                 $hash
     *
     * @return bool
     */
    public function setWithHash($key, $value, $ttl, string $hash): bool
    {
        if ($this->set($key, $value, $ttl) === false) {
            return false;
        }

        try {
            Util::getFS()->touch($this->getHashFilePathname($key, $hash));
        } catch (Exception $e) {
            $this->builder->getLogger()->warning($e->getMessage());

            return false;
        }

        return true;
    }

    /**
     * Cache::has() with a forced hash.
     *
     * @param string $key
     * @param string $hash
     *
     * @return bool
     */
    public function hasWithHash(string $key, string $hash): bool
    {
        if (!$this->has($key) || !Util::getFS()->exists($this->getHashFilePathname($key, $hash))) {
            return false;
        }

        return true;
    }
}
