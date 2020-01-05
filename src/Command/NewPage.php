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

use Cecil\Util\Plateform;
use Symfony\Component\Process\Process;
use Zend\Console\Prompt\Confirm;

/**
 * Class NewPage.
 */
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

    /**
     * {@inheritdoc}
     */
    public function processCommand()
    {
        $this->name = $this->getRoute()->getMatchedParam('name');
        $this->force = $this->getRoute()->getMatchedParam('force', false);
        $this->open = $this->getRoute()->getMatchedParam('open', false);
        $this->prefix = $this->getRoute()->getMatchedParam('prefix', false);

        try {
            $path_parts = pathinfo($this->name);
            $dirname = $path_parts['dirname'];
            $filename = $path_parts['filename'];
            $date = date('Y-m-d');
            $title = $filename;

            // date prefix?
            $prefix = '';
            if ($this->prefix) {
                $prefix = sprintf('%s-', $date);
            }

            // path
            $fileRelativePath = sprintf(
                '%s/%s%s%s.md',
                $this->getBuilder()->getConfig()->get('content.dir'),
                !$dirname ?: $dirname.'/',
                $prefix,
                $filename
            );
            $filePath = $this->getPath().'/'.$fileRelativePath;

            // file already exists?
            if ($this->fs->exists($filePath) && !$this->force) {
                if (!Confirm::prompt('This page already exists. Do you want to override it? [y/n]', 'y', 'n')) {
                    exit(0);
                }
            }

            // create new file
            $fileContent = str_replace(
                ['%title%', '%date%'],
                [$title, $date],
                $this->findModel(sprintf('%s%s', !$dirname ?: $dirname.'/', $filename))
            );
            $this->fs->dumpFile($filePath, $fileContent);

            $this->wlDone(sprintf('File "%s" created!', $fileRelativePath));

            // open editor?
            if ($this->open) {
                if (!$this->hasEditor()) {
                    $this->wlAlert('No editor configured!');
                }
                $this->openEditor($filePath);
            }
        } catch (\Exception $e) {
            throw new \Exception(sprintf($e->getMessage()));
        }
    }

    /**
     * Find the page model and return its content.
     *
     * @param string $name
     *
     * @return string
     */
    protected function findModel($name)
    {
        $section = strstr($name, '/', true);
        if ($section && file_exists($model = sprintf('%s/models/%s.md', $this->getPath(), $section))) {
            return file_get_contents($model);
        }
        if (file_exists($model = sprintf('%s/models/default.md', $this->getPath()))) {
            return file_get_contents($model);
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
     * Editor is configured?
     *
     * @return bool
     */
    protected function hasEditor()
    {
        if ($this->builder->getConfig()->get('editor')) {
            return true;
        }

        return false;
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
            switch ($editor) {
                case 'typora':
                    if (Plateform::getOS() == Plateform::OS_OSX) {
                        $command = sprintf('open -a typora "%s"', $filePath);
                    }
                    break;
                default:
                    $command = sprintf('%s "%s"', $editor, $filePath);
                    break;
            }
            $process = new Process($command);
            $process->run();
            if (!$process->isSuccessful()) {
                throw new \Exception(sprintf('Can\'t run "%s".', $command));
            }
        }
    }
}
