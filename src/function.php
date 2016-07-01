<?php
/*
 * This file is part of the PHPoole package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if (!function_exists('isPhar')) {
    function isPhar()
    {
        if (!empty(\Phar::running())) {
            return true;
        }

        return false;
    }
}
