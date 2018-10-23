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
     * @var string
     */
    protected $name;
    /**
     * @var bool
     */
    protected $force;

    public function processCommand()
    {
        $this->name = $this->getRoute()->getMatchedParam('name');
        $this->force = $this->getRoute()->getMatchedParam('force', false);

        try {
            // file content
            $fileContent = <<<'EOT'
---
title: '%title%'
date: '%date%'
draft: true
---

EOT;
            if (file_exists($archetype = $this->getPath().'/archetypes/default.md')) {
                $fileContent = file_get_contents($archetype);
            }
            // file name (without extension)
            if (false !== $extPos = strripos($this->name, '.md')) {
                $this->name = substr($this->name, 0, $extPos);
            }
            $fileRelativePath = $this->getPHPoole()->getConfig()->get('content.dir').'/'.$this->name.'.md';
            $filePath = $this->getPath().'/'.$fileRelativePath;
            if ($this->fs->exists($filePath) && !$this->force) {
                if (!Confirm::prompt('This page already exists. Do you want to override it? [y/n]', 'y', 'n')) {
                    exit(0);
                }
            }
            $title = $this->name;
            if (false !== strrchr($this->name, '/')) {
                $title = substr(strrchr($this->name, '/'), 1);
            }
            $date = date('Y-m-d');
            $fileContent = str_replace(["%title%", "%date%"], [$title, $date], $fileContent);
            $this->fs->dumpFile($filePath, $fileContent);
            $this->wlDone(sprintf('File "%s" created!', $fileRelativePath));
            $editor = 'atom';
            passthru (sprintf("%s %s", $editor, $filePath), $return_var);
        } catch (\Exception $e) {
            throw new \Exception(sprintf($e->getMessage()));
        }
    }
}
