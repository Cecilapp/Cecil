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

namespace Cecil\Test\Unit\Util;

use Cecil\Util\Date;
use PHPUnit\Framework\TestCase;

class DateTest extends TestCase
{
    public function testIsValidRecognizesExpectedFormat(): void
    {
        self::assertTrue(Date::isValid('2026-08-20'));
        self::assertFalse(Date::isValid('2026-13-99'));
    }

    public function testToDatetimeRejectsNull(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("can't be null");

        Date::toDatetime(null);
    }

    public function testToDatetimeSupportsDateTimeAndImmutable(): void
    {
        $dateTime = new \DateTime('2024-01-01T00:00:00+00:00');
        self::assertSame($dateTime, Date::toDatetime($dateTime));

        $immutable = new \DateTimeImmutable('2024-01-01T00:00:00+00:00');
        $converted = Date::toDatetime($immutable);
        self::assertInstanceOf(\DateTime::class, $converted);
        self::assertSame($immutable->getTimestamp(), $converted->getTimestamp());
    }

    public function testToDatetimeSupportsTimestampAndString(): void
    {
        $fromTimestamp = Date::toDatetime(0);
        self::assertSame(0, $fromTimestamp->getTimestamp());

        $fromString = Date::toDatetime('2024-02-20 12:34:56');
        self::assertInstanceOf(\DateTime::class, $fromString);
        self::assertSame('2024-02-20', $fromString->format('Y-m-d'));
    }

    public function testToDatetimeRejectsWrongType(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('must be a string, an integer timestamp');

        Date::toDatetime([]);
    }

    public function testDurationToIso8601RoundsAndFormats(): void
    {
        self::assertSame('PT00H00M3661S', Date::durationToIso8601(3661));
        self::assertSame('PT00H00M01S', Date::durationToIso8601(0.6));
    }
}
