<?php declare(strict_types=1);

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
use Symfony\Component\Console\Question\ConfirmationQuestion;

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
        $name = (string) $input->getArgument('name');
        $force = $input->getOption('force');
        $open = $input->getOption('open');
        $prefix = $input->getOption('prefix');

        try {
            $nameParts = pathinfo($name);
            $dirname = trim($nameParts['dirname'], '.');
            $filename = $nameParts['filename'];
            $date = date('Y-m-d');
            $title = ucfirst($filename);
            // has date prefix?
            $datePrefix = '';
            if ($prefix) {
                $datePrefix = \sprintf('%s-', $date);
            }
            // path
            $fileRelativePath = \sprintf(
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
                $output->writeln(\sprintf(
                    '<comment>The file "%s" already exists.</comment>',
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
            $model = $this->findModel(\sprintf('%s%s', empty($dirname) ? '' : $dirname.DIRECTORY_SEPARATOR, $filename));
            $fileContent = str_replace(
                ['%title%', '%date%'],
                [$title, $date],
                $model['content']
            );
            $this->fs->dumpFile($filePath, $fileContent);
            $output->writeln(\sprintf('<info>File "%s" created (with model "%s").</info>', $fileRelativePath, $model['name']));

            // open editor?
            if ($open) {
                if (null === $editor = $input->getOption('editor')) {
                    if (!$this->getBuilder()->getConfig()->has('editor')) {
                        $output->writeln('<comment>No editor configured.</comment>');

                        return 0;
                    }
                    $editor = (string) $this->getBuilder()->getConfig()->get('editor');
                }
                $output->writeln(\sprintf('<info>Opening file with %s...</info>', ucfirst($editor)));
                $this->openEditor($filePath, $editor);
            }
        } catch (\Exception $e) {
            throw new RuntimeException(\sprintf($e->getMessage()));
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
