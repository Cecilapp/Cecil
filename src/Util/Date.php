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

namespace Cecil\Util;

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
     * e.g.: '00:00:46.70' -> 'T0M46S'
     */
    public static function durationToIso8601(string $duration): string
    {
        $time = new \DateTime($duration);
        $midnight = new \DateTime();
        $midnight->setTime(0,0);
        $period = $midnight->diff($time);

        return $period->format('T%iM%SS');
    }
}
