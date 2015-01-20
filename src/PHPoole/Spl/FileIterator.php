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

use FilterIterator;
use PHPoole\Spl\FileInfo;

/**
 * PHPoole File iterator
 *
 * Class FileIterator
 * @package PHPoole\Spl
 */
class FileIterator extends FilterIterator
{
    /**
     * @var null|string
     */
    protected $_extFilter = null;

    /**
     * @param string $dirOrIterator
     * @param string $extFilter
     */
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

    /**
     * @return bool
     */
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