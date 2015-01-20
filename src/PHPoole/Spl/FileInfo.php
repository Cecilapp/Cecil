<?php
/*
 * This file is part of the PHPoole package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Spl;

use SplFileInfo;

/**
 * PHPoole File
 *
 * Class FileInfo
 * @package PHPoole\Spl
 */
class FileInfo extends SplFileInfo
{
    /**
     * @var array
     */
    protected $_data = array();

    /**
     * @param $key
     * @param $value
     * @return $this
     */
    public function setData($key, $value)
    {
        $this->_data[$key] = $value;
        return $this;
    }

    /**
     * @param string $key
     * @return array
     */
    public function getData($key='')
    {
        if ($key == '') {
            return $this->_data;
        }
        if (isset($this->_data[$key])) {
            return $this->_data[$key];
        }
    }

    /**
     * @return string
     */
    public function getContents()
    {
        $level = error_reporting(0);
        $content = file_get_contents($this->getRealpath());
        error_reporting($level);
        if (false === $content) {
            $error = error_get_last();
            throw new \RuntimeException($error['message']);
        }
        return $content;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function parse()
    {
        if (!$this->isReadable()) {
            throw new \Exception('Cannot read file');
        }
        // parse front matter
        preg_match('/^<!--(.+)-->(.+)/s', $this->getContents(), $matches);
        // if not front matter, return content only
        if (!$matches) {
            $this->setData('content_raw', $this->getContents());
            return $this;
        }
        // $rawInfo    = front matter data
        // $rawContent = content data
        list($matchesAll, $rawInfo, $rawContent) = $matches;
        // parse front matter
        $info = parse_ini_string($rawInfo);
        $this->setData('info', $info);
        $this->setData('content_raw', $rawContent);
        return $this;
    }
}