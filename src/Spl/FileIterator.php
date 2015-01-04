<?php
namespace PHPoole\Spl;

use FilterIterator;
use PHPoole\Spl\FileInfo;

/**
 * PHPoole File iterator
 */
class FileIterator extends FilterIterator
{
    protected $_extFilter = null;

    public function __construct($dirOrIterator = '.', $extFilter='')
    {
        if (is_string($dirOrIterator)) {
            if (!is_dir($dirOrIterator)) {
                throw new \InvalidArgumentException('Expected a valid directory name');
            }
            $dirOrIterator = new \RecursiveDirectoryIterator(
                $dirOrIterator,
                \FilesystemIterator::UNIX_PATHS
                |\RecursiveIteratorIterator::SELF_FIRST
            );
        }
        elseif (!$dirOrIterator instanceof \DirectoryIterator) {
            throw new \InvalidArgumentException('Expected a DirectoryIterator');
        }
        if ($dirOrIterator instanceof \RecursiveIterator) {
            $dirOrIterator = new \RecursiveIteratorIterator($dirOrIterator);
        }
        if (!empty($extFilter)) {
            $this->_extFilter = $extFilter;
        }
        parent::__construct($dirOrIterator);
        $this->setInfoClass('PHPoole\Spl\FileInfo');
    }

    public function accept()
    {
        $file = $this->getInnerIterator()->current();
        if (!$file instanceof FileInfo) {
            return false;
        }
        if (!$file->isFile()) {
            return false;
        }
        if (!is_null($this->_extFilter)) {
            if ($file->getExtension() != $this->_extFilter) {
                return false;
            }
            return true;
        }
        return true;
    }
}