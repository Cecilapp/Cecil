<?php
/**
 * This file is part of the Cecil/Cecil package.
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
    public static function isDateValid(string $date, string $format = 'Y-m-d'): bool
    {
        $d = \DateTime::createFromFormat($format, $date);

        return $d && $d->format($format) === $date;
    }

    /**
     * Date to DateTime.
     *
     * @param mixed $date
     */
    public static function dateToDatetime($date): \DateTime
    {
        // DateTime
        if ($date instanceof \DateTime) {
            return $date;
        }
        // timestamp or 'AAAA-MM-DD'
        if (is_numeric($date)) {
            return (new \DateTime())->setTimestamp($date);
        }
        // string (ie: '01/01/2019', 'today')
        return new \DateTime($date);
    }
}
