<?php
/*
 * This file is part of the PHPoole package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Command;

class Config extends AbstractCommand
{
    public function processCommand()
    {
        try {
            $this->wl($this->printArray($this->getPHPoole()->getConfig()->getAllAsArray()));
        } catch (\Exception $e) {
            throw new \Exception(sprintf($e->getMessage()));
        }
    }

    private function printArray($array, $column = -4)
    {
        $output = '';

        if (is_array($array)) {
            $column += 4;
            foreach ($array as $key=>$val) {
                if (is_array($val)) {
                    $output .= str_repeat(' ', $column)."$key:\n".$this->printArray($val, $column);
                }
                if (is_string($val)) {
                    $output .= str_repeat(' ', $column)."$key: $val\n";
                }
            }
        }

        return $output;
    }
}
