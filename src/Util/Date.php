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
    public static function toDatetime($date): \DateTimeImmutable
    {
        if ($date === null) {
            throw new \Exception('$date can\'t be null.');
        }
        // DateTimeImmutable
        if ($date instanceof \DateTimeImmutable) {
            return $date;
        }
        // DateTime
        if ($date instanceof \DateTime) {
            return \DateTimeImmutable::createFromMutable($date);
        }
        // timestamp
        if (\is_int($date)) {
            return (new \DateTimeImmutable())->setTimestamp($date);
        }

        return new \DateTimeImmutable($date);
    }
}
