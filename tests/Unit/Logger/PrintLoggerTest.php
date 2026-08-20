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

namespace Cecil\Test\Unit\Logger;

use Cecil\Builder;
use Cecil\Logger\PrintLogger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

class PrintLoggerTest extends TestCase
{
    public function testLogThrowsForUnknownLevel(): void
    {
        $logger = new PrintLogger();

        $this->expectException(\Psr\Log\InvalidArgumentException::class);
        $this->expectExceptionMessage('does not exist');

        $logger->log('unknown', 'hello');
    }

    public function testInfoAndErrorFormatting(): void
    {
        $logger = new PrintLogger();

        ob_start();
        $logger->log(LogLevel::INFO, 'Hello {name}', ['name' => 'Cecil']);
        $logger->log(LogLevel::ERROR, 'Boom');
        $output = ob_get_clean();

        self::assertStringContainsString("Hello Cecil\n", $output);
        self::assertStringContainsString("[error] Boom\n", $output);
    }

    public function testProgressContextIsPrinted(): void
    {
        $logger = new PrintLogger();

        ob_start();
        $logger->log(LogLevel::INFO, 'Build', ['progress' => [1, 3]]);
        $output = ob_get_clean();

        self::assertStringContainsString('Build (1/3)', $output);
    }

    public function testVerbosityFilterSkipsHigherLevels(): void
    {
        $logger = new PrintLogger(Builder::VERBOSITY_NORMAL);

        ob_start();
        $logger->log(LogLevel::DEBUG, 'hidden');
        $output = ob_get_clean();

        self::assertSame('', $output);
    }

    public function testInterpolationSupportsNonScalarValues(): void
    {
        $logger = new PrintLogger();
        $date = new \DateTimeImmutable('2026-01-01T10:00:00+00:00');
        $object = new class () {
            public function __toString(): string
            {
                return 'obj';
            }
        };

        ob_start();
        $logger->log(LogLevel::INFO, '{date} {object} {array}', [
            'date' => $date,
            'object' => $object,
            'array' => ['x'],
        ]);
        $output = ob_get_clean();

        self::assertStringContainsString('2026-01-01T10:00:00+00:00', $output);
        self::assertStringContainsString('obj', $output);
        self::assertStringContainsString('[array]', $output);
    }

    public function testFormatRemovesSpacesAndNewLines(): void
    {
        self::assertSame("'foobar'", PrintLogger::format("foo\n bar"));
    }
}
