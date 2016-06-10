<?php
/*
 * This file is part of the PHPoole package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole;

/**
 * PHPoole plugin abstract.
 *
 * Class Plugin
 */
abstract class Plugin
{
    const DEBUG = false;

    /**
     * @param $name
     * @param $args
     */
    public function __call($name, $args)
    {
        if (self::DEBUG) {
            printf("[EVENT] %s is not implemented in %s plugin\n", $name, get_class(__FUNCTION__));
        }
    }

    /**
     * @param bool $enabled
     * @param $e
     */
    public function trace($enabled, $e)
    {
        if ($enabled === true) {
            printf(
                '[EVENT] %s\%s %s'."\n",
                get_class($this),
                $e->getName(),
                json_encode($e->getParams())
            );
        }
    }
}
