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
use Dotenv\Dotenv;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
class DotenvTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cecil-dotenv-test-' . uniqid('', true);
        mkdir($this->tmpDir);
    }

    protected function tearDown(): void
    {
        $envFile = $this->tmpDir . '/.env';
        if (file_exists($envFile)) {
            unlink($envFile);
        }
        rmdir($this->tmpDir);
    }

    public function testDotenvLoadsVariablesViaGetenv(): void
    {
        file_put_contents($this->tmpDir . '/.env', "CECIL_TITLE=\"Hello Dotenv\"\n");

        Dotenv::createUnsafeImmutable($this->tmpDir)->load();

        self::assertSame('Hello Dotenv', getenv('CECIL_TITLE'));
    }

    public function testDotenvVariablesAreInjectedIntoConfig(): void
    {
        file_put_contents($this->tmpDir . '/.env', "CECIL_TITLE=\"My Dotenv Site\"\nCECIL_BASEURL=https://example.com/\n");

        Dotenv::createUnsafeImmutable($this->tmpDir)->load();

        $config = new Config();
        $config->import([]);

        self::assertSame('My Dotenv Site', $config->get('title'));
        self::assertSame('https://example.com/', $config->get('baseurl'));
    }

    public function testSafeLoadDoesNotThrowWhenEnvFileIsMissing(): void
    {
        $this->expectNotToPerformAssertions();

        Dotenv::createUnsafeImmutable($this->tmpDir)->safeLoad();
    }
}
