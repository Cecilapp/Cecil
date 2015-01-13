<?php
namespace PHPoole\Spl;

use SplFileInfo;

/**
 * PHPoole FileInfo, extended from SplFileInfo
 */
class FileInfo extends SplFileInfo
{
    protected $_data = array();

    public function setData($key, $value)
    {
        $this->_data[$key] = $value;
        return $this;
    }

    public function getData($key='')
    {
        if ($key == '') {
            return $this->_data;
        }
        if (isset($this->_data[$key])) {
            return $this->_data[$key];
        }
    }

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