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

namespace Cecil\Test\Unit;

use Cecil\Config;
use Cecil\Exception\ConfigException;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function testEmptyCacheDirectoryIsRejectedWhenCacheIsEnabled(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('The cache directory (`cache.dir`) must not be empty when cache is enabled.');

        new Config([
            'cache' => [
                'dir' => '',
            ],
        ]);
    }

    public function testInvalidOutputFormatsStructureIsRejected(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Output format #14 must be an array.');

        new Config([
            'output' => [
                'formats' => ['html'],
            ],
        ]);
    }
}
