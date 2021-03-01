<?php
/**
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Command;

use Cecil\Util;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Process\Process;

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
                    new InputArgument('name', InputArgument::REQUIRED, 'New page name'),
                    new InputArgument('path', InputArgument::OPTIONAL, 'Use the given path as working directory'),
                    new InputOption('force', 'f', InputOption::VALUE_NONE, 'Override the file if already exist'),
                    new InputOption('open', 'o', InputOption::VALUE_NONE, 'Open editor automatically'),
                    new InputOption('prefix', 'p', InputOption::VALUE_NONE, 'Add date (`YYYY-MM-DD`) as a prefix'),
                ])
            )
            ->setHelp('Creates a new page file (with a default title and the current date)');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = (string) $input->getArgument('name');
        $force = $input->getOption('force');
        $open = $input->getOption('open');
        $prefix = $input->getOption('prefix');

        try {
            $nameParts = pathinfo($name);
            $dirname = trim($nameParts['dirname'], '.');
            $filename = $nameParts['filename'];
            $date = date('Y-m-d');
            $title = $filename;
            // has date prefix?
            $datePrefix = '';
            if ($prefix) {
                $datePrefix = sprintf('%s-', $date);
            }
            // path
            $fileRelativePath = sprintf(
                '%s%s%s%s%s.md',
                (string) $this->getBuilder()->getConfig()->get('content.dir'),
                DIRECTORY_SEPARATOR,
                empty($dirname) ? '' : $dirname.DIRECTORY_SEPARATOR,
                $datePrefix,
                $filename
            );
            $filePath = Util::joinFile($this->getPath(), $fileRelativePath);

            // file already exists?
            if ($this->fs->exists($filePath) && !$force) {
                $output->writeln(sprintf(
                    '<comment>The page "%s" already exists.</comment>',
                    $fileRelativePath
                ));
                // ask to override file
                $helper = $this->getHelper('question');
                $question = new ConfirmationQuestion('Do you want to override it? [y/n]', false);
                if (!$helper->ask($input, $output, $question)) {
                    return 0;
                }
            }

            // creates a new file
            $fileContent = str_replace(
                ['%title%', '%date%'],
                [$title, $date],
                $this->findModel(sprintf('%s%s', empty($dirname) ? '' : $dirname.DIRECTORY_SEPARATOR, $filename))
            );
            $this->fs->dumpFile($filePath, $fileContent);
            $output->writeln(sprintf('<info>File "%s" created.</info>', $fileRelativePath));

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
     * Finds the page model and returns its content.
     *
     * @param string $name
     *
     * @return string
     */
    private function findModel(string $name): string
    {
        $section = strstr($name, DIRECTORY_SEPARATOR, true);
        if ($section && file_exists($model = Util::joinFile($this->getPath(), 'models', "$section.md"))) {
            return Util::fileGetContents($model);
        }
        if (file_exists($model = Util::joinFile($this->getPath(), 'models/default.md'))) {
            return Util::fileGetContents($model);
        }

        return <<<'EOT'
---
title: "%title%"
date: %date%
draft: true
---
_Your content here_

EOT;
    }

    /**
     * Editor is configured?
     *
     * @return bool
     */
    protected function hasEditor(): bool
    {
        return (bool) $this->getBuilder()->getConfig()->get('editor');
    }

    /**
     * Opens the new file in editor (if configured).
     *
     * @param string $filePath
     *
     * @return void
     */
    protected function openEditor(string $filePath): void
    {
        if ($editor = (string) $this->getBuilder()->getConfig()->get('editor')) {
            $command = sprintf('%s "%s"', $editor, $filePath);
            // Typora 4TW!
            if ($editor == 'typora') {
                if (Util\Plateform::getOS() == Util\Plateform::OS_OSX) {
                    $command = sprintf('open -a typora "%s"', $filePath);
                }
            }
            $process = Process::fromShellCommandline($command);
            $process->run();
            if (!$process->isSuccessful()) {
                throw new \Exception(sprintf('Can\'t run "%s".', $command));
            }
        }
    }
}
