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
 * Proxy class used by the template engine
 * "site.data" = "class.method".
 *
 * Class Proxy
 */
class Proxy
{
    /**
     * @var
     */
    protected $_phpoole;

    /**
     * @param $phpoole
     *
     * @throws \Exception
     */
    public function __construct($phpoole)
    {
        if (!$phpoole instanceof PHPoole) {
            throw new \Exception('Proxy should be loaded with a PHPoole instance');
        }
        $this->_phpoole = $phpoole;
    }

    /**
     * Magic method can get call like $site->name(), etc.
     *
     * @todo do it better! :-)
     *
     * @param $function
     * @param $arguments
     *
     * @return null
     */
    public function __call($function, $arguments)
    {
        /*
        if (!method_exists($this->_phpoole, $function)) {
            throw new Exception(sprintf('Proxy erreor: Cannot get %s', $function));
        }
        return call_user_func_array(array($this->_phpoole, $function), $arguments);
        */
        $config = $this->_phpoole->getConfig();
        if (array_key_exists($function, $config['site'])) {
            if ($this->_phpoole->isLocalServe()) {
                $configToMerge['site']['base_url'] = 'http://localhost:8000';
                $config = array_replace_recursive($config, $configToMerge);
            }

            return $config['site'][$function];
        }
        if ($function == 'author') {
            return $config['author'];
        }
        if ($function == 'source') {
            return $config['deploy'];
        }
    }

    /**
     * @param string $subDir
     *
     * @return array
     */
    public function getPages($subDir = '')
    {
        return $this->_phpoole->getPages($subDir);
    }
}
