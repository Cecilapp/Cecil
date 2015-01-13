<?php
namespace PHPoole;

/**
 * PHPoole plugin abstract
 */
abstract class Plugin
{
    const DEBUG = false;

    public function __call($name, $args)
    {
        if (self::DEBUG) {
            printf("[EVENT] %s is not implemented in %s plugin\n", $name, get_class(__FUNCTION__));
        }
    }

    public function trace($enabled=self::DEBUG, $e)
    {
        if ($enabled === true) {
            printf(
                '[EVENT] %s\%s %s' . "\n",
                get_class($this),
                $e->getName(),
                json_encode($e->getParams())
            );
        }
    }
}