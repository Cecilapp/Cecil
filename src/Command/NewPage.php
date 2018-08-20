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

use Zend\Console\Prompt\Confirm;

class NewPage extends AbstractCommand
{
    /**
     * @var bool
     */
    protected $force;

    public function processCommand()
    {
        $this->name = $this->getRoute()->getMatchedParam('name');
        $this->force = $this->getRoute()->getMatchedParam('force', false);

        try {
            $fileContent = <<<'EOT'
---
title: '%s'
date: '%s'
draft: true
---
# New page
EOT;
            $filePath = $this->getPath().'/'.$this->getPHPoole()->getConfig()->get('content.dir').'/'.$this->name.'.md';

            if ($this->fs->exists($filePath) && !$this->force) {
                if (!Confirm::prompt('This page already exists. Do you want to override it? [y/n]', 'y', 'n')) {
                    exit(0);
                }
            }
            $this->fs->dumpFile($filePath, sprintf($fileContent, $this->name, date('Y-m-d')));
            $this->wlDone('Done!');
        } catch (\Exception $e) {
            throw new \Exception(sprintf($e->getMessage()));
        }
    }
}
