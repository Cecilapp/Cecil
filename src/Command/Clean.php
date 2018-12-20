<?php
/*
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Command;

/**
 * Class Clean.
 */
class Clean extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    public function processCommand()
    {
        $outputDir = $this->getBuilder()->getConfig()->get('output.dir');
        if ($this->fs->exists($this->getPath().'/'.Serve::$tmpDir.'/output')) {
            $outputDir = file_get_contents($this->getPath().'/'.Serve::$tmpDir.'/output');
        }
        // delete output dir
        if ($this->fs->exists($this->getPath().'/'.$outputDir)) {
            $this->fs->remove($this->getPath().'/'.$outputDir);
            $this->wlDone(sprintf("Output directory '%s' removed.", $outputDir));
        }
        // delete local server temp files
        if ($this->fs->exists($this->getPath().'/'.Serve::$tmpDir)) {
            $this->fs->remove($this->getPath().'/'.Serve::$tmpDir);
            $this->wlDone('Temporary server files deleted.');
        }
        exit(0);
    }
}
