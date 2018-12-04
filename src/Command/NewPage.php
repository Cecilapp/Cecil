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

use Symfony\Component\Process\Process;
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
            // file name (without extension)
            if (false !== $extPos = strripos($this->name, '.md')) {
                $this->name = substr($this->name, 0, $extPos);
            }
            // path
            $fileRelativePath = $this->getBuilder()->getConfig()->get('content.dir').'/'.$this->name.'.md';
            $filePath = $this->getPath().'/'.$fileRelativePath;

            // file already exists?
            if ($this->fs->exists($filePath) && !$this->force) {
                if (!Confirm::prompt('This page already exists. Do you want to override it? [y/n]', 'y', 'n')) {
                    exit(0);
                }
            }

            // create new file
            $title = $this->name;
            if (false !== strrchr($this->name, '/')) {
                $title = substr(strrchr($this->name, '/'), 1);
            }
            $date = date('Y-m-d');
            $fileContent = str_replace(['%title%', '%date%'], [$title, $date], $this->findArchetype($this->name));
            $this->fs->dumpFile($filePath, $fileContent);
            $this->wlDone(sprintf('File "%s" created!', $fileRelativePath));
            // open editor?
            $this->openEditor($filePath);
        } catch (\Exception $e) {
            throw new \Exception(sprintf($e->getMessage()));
        }
    }

    /**
     * Find the archtetype and return its content.
     *
     * @param string $name
     *
     * @return string
     */
    protected function findArchetype($name)
    {
        $section = strstr($this->name, '/', true);
        if ($section && file_exists($archetype = sprintf('%s/archetypes/%s.md', $this->getPath(), $section))) {
            return file_get_contents($archetype);
        }
        if (file_exists($archetype = sprintf('%s/archetypes/default.md', $this->getPath()))) {
            return file_get_contents($archetype);
        }

        return <<<'EOT'
---
title: '%title%'
date: '%date%'
draft: true
---

_[Your content here]_
EOT;
    }

    /**
     * Open new file in editor (if configured).
     *
     * @param string $filePath
     *
     * @return void
     */
    protected function openEditor($filePath)
    {
        if ($editor = $this->builder->getConfig()->get('editor')) {
            $command = sprintf('%s %s', $editor, $filePath);
            $process = new Process($command);
            $process->run();
            if (!$process->isSuccessful()) {
                throw new \Exception(sprintf("Can't open '%s' editor.", $editor));
            }
        }
    }
}
