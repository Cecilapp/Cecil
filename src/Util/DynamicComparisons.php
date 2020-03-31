<?php
/*
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Util;

trait DynamicComparisons
{
    private $operatorToMethod = [
        '=='  => 'equal',
        '===' => 'totallyEqual',
        '!='  => 'notEqual',
        '>'   => 'greaterThan',
        '<'   => 'lessThan',
    ];

    protected function is($valueA, $operation, $valueB)
    {
        if ($method = $this->operatorToMethod[$operation]) {
            return $this->$method($valueA, $valueB);
        }

        throw new \Exception('Unknown Dynamic Operator.');
    }

    private function equal($valueA, $valueB)
    {
        return $valueA == $valueB;
    }

    private function totallyEqual($valueA, $valueB)
    {
        return $valueA === $valueB;
    }

    private function notEqual($valueA, $valueB)
    {
        return $valueA != $valueB;
    }

    private function greaterThan($valueA, $valueB)
    {
        return $valueA > $valueB;
    }

    private function lessThan($valueA, $valueB)
    {
        return $valueA < $valueB;
    }

    private function greaterThanOrEqual($valueA, $valueB)
    {
        return $valueA >= $valueB;
    }

    private function lessThanOrEqual($valueA, $valueB)
    {
        return $valueA <= $valueB;
    }
}
