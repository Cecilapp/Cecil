<?php
/**
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Assets;

use Cecil\Exception\Exception;
use Cecil\Util;

class Asset extends AbstractAsset
{
    protected $asset = [];
    protected $fileLoaded = false;

    /**
     * Loads a file.
     *
     * @param string $path
     *
     * @return self
     */
    public function getFile(string $path): self
    {
        $filePath = Util::joinFile($this->config->getStaticPath(), $path);

        if (!Util::getFS()->exists($filePath)) {
            throw new Exception(sprintf('Asset file "%s" doesn\'t exist.', $path));
        }
        $this->fileLoaded = true;

        $fileInfo = new \SplFileInfo($filePath);

        $this->asset['path'] = $path;
        $this->asset['ext'] = $fileInfo->getExtension();

        return $this;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->asset['path'];
    }

    public function getHtml(): string
    {
        if (!$this->fileLoaded) {
            throw new Exception(\sprintf('%s() error: you must load a file first.', __FUNCTION__));
        }

        switch ($this->asset['ext']) {
            case 'css':
                return \sprintf('<link rel="stylesheet" href="%s">', $this->asset['path']);
                break;
            default:
                return 'POUET';
                break;
        }
    }
}
