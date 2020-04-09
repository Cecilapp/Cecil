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
use Cecil\Assets\AbstractAsset;
use Cecil\Util;

class Asset extends AbstractAsset
{
    protected $asset = [];

    /**
     * Loads a file.
     *
     * @param string $path
     *
     * @return self
     */
    public function load(string $path): self
    {
        $filePath = Util::joinFile($this->config->getStaticPath(), $path);

        if (!Util::getFS()->exists($filePath)) {
            throw new Exception(sprintf('Asset file "%s" doesn\'t exist.', $path));
        }

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

        //return \sprintf('<link rel="stylesheet" href="%s">', $this->asset['path']);
    }
}
