<?php

declare(strict_types=1);

/*
 * This file is part of Cecil.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Command;

use Cecil\Exception\RuntimeException;
use Cecil\Util;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Creates a new page.
 */
class NewPage extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('new:page')
            ->setDescription('Creates a new page')
            ->setDefinition(
                new InputDefinition([
                    new InputArgument('path', InputArgument::OPTIONAL, 'Use the given path as working directory'),
                    new InputOption('name', null, InputOption::VALUE_REQUIRED, 'Page path name'),
                    new InputOption('prefix', 'p', InputOption::VALUE_NONE, 'Prefix the file name with the current date (`YYYY-MM-DD`)'),
                    new InputOption('force', 'f', InputOption::VALUE_NONE, 'Override the file if already exist'),
                    new InputOption('open', 'o', InputOption::VALUE_NONE, 'Open editor automatically'),
                    new InputOption('editor', null, InputOption::VALUE_REQUIRED, 'Editor to use with open option'),
                ])
            )
            ->setHelp('Creates a new page file (with filename as title)');
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = (string) $input->getOption('name');
        $prefix = $input->getOption('prefix');
        $force = $input->getOption('force');
        $open = $input->getOption('open');
        $editor = $input->getOption('editor');

        try {
            // ask
            if (empty($name)) {
                $name = $this->io->ask('What is the name of the page file?', 'new-page.md');
                $prefix = $this->io->confirm('Add date prefix to the filename?', false);
                $open = $this->io->confirm('Do you want open the created file with your editor?', false);
                if ($open && !$this->getBuilder()->getConfig()->has('editor')) {
                    $editor = $this->io->ask('Which editor?');
                }
            }
            // parse given path name
            $nameParts = pathinfo($name);
            $dirname = trim($nameParts['dirname'], '.');
            $basename = $nameParts['basename'];
            $extension = $nameParts['extension'];
            $title = substr($basename, 0, -\strlen(".$extension"));
            $filename = $basename;
            if (!\in_array($extension, (array) $this->getBuilder()->getConfig()->get('pages.ext'))) {
                $title = $filename;
                $filename = "$basename.md"; // force a valid extension
            }
            $title = ucfirst(str_replace('-', ' ', $title));
            $date = date('Y-m-d');
            // date prefix?
            $datePrefix = $prefix ? sprintf('%s-', $date) : '';
            // define target path
            $fileRelativePath = sprintf(
                '%s%s%s%s%s',
                (string) $this->getBuilder()->getConfig()->get('pages.dir'),
                DIRECTORY_SEPARATOR,
                empty($dirname) ? '' : $dirname . DIRECTORY_SEPARATOR,
                $datePrefix,
                $filename
            );
            $filePath = Util::joinFile($this->getPath(), $fileRelativePath);
            // ask to override existing file?
            if (Util\File::getFS()->exists($filePath) && !$force) {
                $output->writeln(sprintf('<comment>The file "%s" already exists.</comment>', $fileRelativePath));
                if (!$this->io->confirm('Do you want to override it?', false)) {
                    return 0;
                }
            }
            // creates a new file
            $model = $this->findModel(sprintf('%s%s', empty($dirname) ? '' : $dirname . DIRECTORY_SEPARATOR, $filename));
            $fileContent = str_replace(
                ['%title%', '%date%'],
                [$title, $date],
                $model['content']
            );
            Util\File::getFS()->dumpFile($filePath, $fileContent);
            $output->writeln(sprintf('<info>File "%s" created (with model "%s").</info>', $fileRelativePath, $model['name']));
            // open editor?
            if ($open) {
                if ($editor === null) {
                    if (!$this->getBuilder()->getConfig()->has('editor')) {
                        $output->writeln('<comment>No editor configured.</comment>');

                        return 0;
                    }
                    $editor = (string) $this->getBuilder()->getConfig()->get('editor');
                }
                $output->writeln(sprintf('<info>Opening file with %s...</info>', ucfirst($editor)));
                $this->openEditor($filePath, $editor);
            }
        } catch (\Exception $e) {
            throw new RuntimeException(sprintf($e->getMessage()));
        }

        return 0;
    }

    /**
     * Finds the page model and returns its [name, content].
     */
    private function findModel(string $name): array
    {
        $name = strstr($name, DIRECTORY_SEPARATOR, true) ?: 'default';
        if (file_exists($model = Util::joinFile($this->getPath(), 'models', "$name.md"))) {
            return [
                'name'    => $name,
                'content' => Util\File::fileGetContents($model),
            ];
        }

        $content = <<<'EOT'
---
title: "%title%"
date: %date%
published: true
---
_Your content here_

EOT;

        return [
            'name'    => 'cecil',
            'content' => $content,
        ];
    }
}
