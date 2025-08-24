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

namespace Cecil\Util;

/**
 * Date utility class.
 *
 * This class provides utility methods for handling dates,
 * including validation, conversion to DateTime, and formatting durations.
 */
class Date
{
    /**
     * Checks if a date is valid.
     */
    public static function isValid(string $date, string $format = 'Y-m-d'): bool
    {
        $d = \DateTime::createFromFormat($format, $date);

        return $d && $d->format($format) === $date;
    }

    /**
     * Date to DateTime.
     *
     * @param mixed $date
     */
    public static function toDatetime($date): \DateTime
    {
        if ($date === null) {
            throw new \Exception('$date can\'t be null.');
        }
        // DateTime
        if ($date instanceof \DateTime) {
            return $date;
        }
        // DateTimeImmutable
        if ($date instanceof \DateTimeImmutable) {
            return \DateTime::createFromImmutable($date);
        }
        // timestamp
        if (\is_int($date)) {
            return (new \DateTime())->setTimestamp($date);
        }

        return new \DateTime($date);
    }

    /**
     * Duration in seconds to ISO 8601.
     */
    public static function durationToIso8601(float $duration): string
    {
        $duration = (int) round($duration);
        $dateInterval = \DateInterval::createFromDateString("$duration seconds");

        return $dateInterval->format('PT%HH%IM%SS');
    }
}
