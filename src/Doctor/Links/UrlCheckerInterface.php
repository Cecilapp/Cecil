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

namespace Cecil\Doctor\Links;

/**
 * Checks the availability of remote URLs.
 */
interface UrlCheckerInterface
{
    /**
     * Checks a list of URLs and returns the result of each one, indexed by URL.
     *
     * - `status`: final HTTP status code (0 if no response)
     * - `error`: transport error message (empty if none)
     * - `errno`: transport error code (0 if none)
     * - `redirect`: final URL if the request has been redirected (empty if none)
     *
     * @param array<int, string>                                       $urls
     * @param array{timeout?: int, concurrency?: int, useragent?: string} $options
     *
     * @return array<string, array{status: int, error: string, errno: int, redirect: string}>
     */
    public function check(array $urls, array $options = []): array;
}
