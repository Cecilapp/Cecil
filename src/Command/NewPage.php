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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Process\Process;

class NewPage extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('new:page')
            ->setDescription('Create a new page')
            ->setDefinition(
                new InputDefinition([
                    new InputArgument('name', InputArgument::REQUIRED, 'New page name'),
                    new InputArgument('path', InputArgument::OPTIONAL, 'Use the given path as working directory'),
                    new InputOption('force', 'f', InputOption::VALUE_NONE, 'Override the file if already exist'),
                    new InputOption('open', 'o', InputOption::VALUE_NONE, 'Open editor automatically'),
                ])
            )
            ->setHelp('Create a new page file (with a default title and the current date).');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = (string) $input->getArgument('name');
        $force = $input->getOption('force');
        $open = $input->getOption('open');

        try {
            // file name (without extension)
            if (false !== $extPos = strripos($name, '.md')) {
                $name = substr($name, 0, $extPos);
            }
            if (null === $name) {
                throw new \Exception('New page\'s name can\'t be empty');
            }

            // path
            $fileRelativePath = $this->getBuilder($output)->getConfig()->get('content.dir').'/'.$name.'.md';
            $filePath = $this->getPath().'/'.$fileRelativePath;

            // file already exists?
            if ($this->fs->exists($filePath) && !$force) {
                $helper = $this->getHelper('question');
                $question = new ConfirmationQuestion(
                    sprintf('This page already exists. Do you want to override it? [y/n]', $this->getpath()),
                    false
                );
                if (!$helper->ask($input, $output, $question)) {
                    return;
                }
            }

            // create new file
            $title = $name;
            if (false !== strrchr($name, '/')) {
                $title = substr(strrchr($name, '/'), 1);
            }
            $date = date('Y-m-d');
            $fileContent = str_replace(['%title%', '%date%'], [$title, $date], $this->findModel($name));
            $this->fs->dumpFile($filePath, $fileContent);

            $output->writeln(sprintf('File "%s" created.', $fileRelativePath));

            // open editor?
            if ($open) {
                if (!$this->hasEditor()) {
                    $output->writeln('<comment>No editor configured.</comment>');
                }
                $this->openEditor($filePath);
            }
        } catch (\Exception $e) {
            throw new \Exception(sprintf($e->getMessage()));
        }

        return 0;
    }

    /**
     * Find the page model and return its content.
     *
     * @param string $name
     *
     * @return string
     */
    protected function findModel(string $name): string
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
    protected function hasEditor(): bool
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
    protected function openEditor(string $filePath)
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
