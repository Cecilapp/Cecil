<?php
/*
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Command;

/**
 * Class Config.
 */
class Config extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    public function processCommand()
    {
        try {
            $this->wl($this->printArray($this->getBuilder()->getConfig()->getAllAsArray()));
        } catch (\Exception $e) {
            throw new \Exception(sprintf($e->getMessage()));
        }
    }

    /**
     * Print an array in console.
     */
    private function printArray($array, $column = -4)
    {
        $output = '';

        if (is_array($array)) {
            $column += 4;
            foreach ($array as $key => $val) {
                if (is_array($val)) {
                    $output .= str_repeat(' ', $column)."$key:\n".$this->printArray($val, $column);
                }
                if (is_string($val) || is_int($val)) {
                    $output .= str_repeat(' ', $column)."$key: $val\n";
                }
                if (is_bool($val)) {
                    $output .= str_repeat(' ', $column)."$key: ".($val ? 'true' : 'false')."\n";
                }
            }
        }

        return $output;
    }
}
