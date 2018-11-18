<?php
/*
 * This file is part of the PHPoole package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Command;

use PHPoole\PHPoole;

class Clean extends AbstractCommand
{
    public function processCommand()
    {
        //delete output dir
        $outputDir = $this->getPHPoole()->getConfig()->get('output.dir');
        if ($this->fs->exists($this->getPath().'/'.$outputDir)) {
            $this->fs->remove($this->getPath().'/'.$outputDir);
            $this->wlDone(sprintf("Output directory '%s' removed.", $outputDir));
        }
        // delete server temp files
        if ($this->fs->exists($this->getPath().'/'.Serve::$tmpDir)) {
            $this->fs->remove($this->getPath().'/'.Serve::$tmpDir);
            $this->wlDone('Temporary server files deleted.');
        }
        exit(0);
    }
}
