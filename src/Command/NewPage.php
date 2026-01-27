<?php

/**
 * This file is part of Cecil.
 *
 * (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cecil\Command;

use Cecil\Exception\RuntimeException;
use Cecil\Util;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * NewPage command.
 *
 * This command creates a new page file in the specified directory.
 * It allows users to define the page name, whether to slugify the file name, add a date prefix,
 * and whether to open the file in an editor after creation.
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
            ->setDefinition([
                new InputArgument('path', InputArgument::OPTIONAL, 'Use the given path as working directory'),
                new InputOption('name', null, InputOption::VALUE_REQUIRED, 'Page path name'),
                new InputOption('slugify', null, InputOption::VALUE_NEGATABLE, 'Slugify file name (or disable --no-slugify)'),
                new InputOption('prefix', 'p', InputOption::VALUE_NONE, 'Prefix the file name with the current date (`YYYY-MM-DD`)'),
                new InputOption('force', 'f', InputOption::VALUE_NONE, 'Override the file if already exist'),
                new InputOption('open', 'o', InputOption::VALUE_NONE, 'Open editor automatically'),
                new InputOption('editor', null, InputOption::VALUE_REQUIRED, 'Editor to use with open option'),
            ])
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</> command creates a new page file.
If your run this command without any options, it will ask you for the page name and others options.

  <info>%command.full_name%</>
  <info>%command.full_name% --name=path/to/a-page.md</>
  <info>%command.full_name% --name=path/to/A Page.md --slugify</>

To create a new page with a <comment>date prefix</comment> (i.e: `YYYY-MM-DD`), run:

  <info>%command.full_name% --prefix</>

To create a new page and open it with an <comment>editor</comment>, run:

  <info>%command.full_name% --open --editor=editor</>

To <comment>override</comment> an existing page, run:

  <info>%command.full_name% --force</>
EOF
            );
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = (string) $input->getOption('name');
        $slugify = $input->getOption('slugify');
        $prefix = (bool) $input->getOption('prefix');
        $force = (bool) $input->getOption('force');
        $open = (bool) $input->getOption('open');
        $editor = $input->getOption('editor');

        try {
            // ask
            if (empty($name)) {
                $name = $this->io->ask('Give a name for the page file:', 'new-page.md');
                $slugify = ($slugify !== null) ? $slugify : $this->io->confirm('Slugify the file name?', true);
                $prefix = ($prefix !== false) ?: $this->io->confirm('Add date prefix to the filename?', false);
                $open = ($open !== false) ?: $this->io->confirm('Open the created file with your editor?', false);
                if ($open && !$this->getBuilder()->getConfig()->has('editor')) {
                    $editor = ($editor !== null) ? $editor : $this->io->ask('Which editor do you want to use?');
                }
            }
            // parse given path name
            $nameParts = pathinfo($name);
            $dirname = trim($nameParts['dirname'], '.');
            $basename = $nameParts['basename'];
            $extension = $nameParts['extension'];
            $title = substr($basename, 0, -\strlen(".$extension"));
            // define file name (and slugify if needed)
            $filename = $slugify ? \Cecil\Collection\Page\Page::slugify($basename) : $basename;
            // check extension
            if (!\in_array($extension, (array) $this->getBuilder()->getConfig()->get('pages.ext'))) {
                $title = $filename;
                $filename = trim("$filename.md"); // add a valid file extension
            }
            $title = trim(ucfirst(str_replace('-', ' ', $title)));
            $date = date('Y-m-d');
            // add date prefix?
            $datePrefix = $prefix ? \sprintf('%s-', $date) : '';
            // define target path
            $fileRelativePath = \sprintf(
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
                $this->io->warning(\sprintf('The file "%s" already exists.', $fileRelativePath));
                if (!$this->io->confirm('Do you want to override it?', false)) {
                    return 0;
                }
            }
            // creates a new file
            $model = $this->findModel(\sprintf('%s%s', empty($dirname) ? '' : $dirname . DIRECTORY_SEPARATOR, $filename));
            $fileContent = str_replace(
                ['%title%', '%date%'],
                [$title, $date],
                $model['content']
            );
            Util\File::getFS()->dumpFile($filePath, $fileContent);
            // done
            $output->write(sprintf("\033\143"));
            $this->io->success(\sprintf('File created with "%s" model at %s', $model['name'], $filePath));
            // open editor?
            if ($open) {
                if ($editor === null) {
                    if (!$this->getBuilder()->getConfig()->has('editor')) {
                        $this->io->caution('No editor configured.');

                        return 0;
                    }
                    $editor = (string) $this->getBuilder()->getConfig()->get('editor');
                }
                $this->io->info(\sprintf('Opening file with %s...', ucfirst($editor)));
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
            'name'    => $name,
            'content' => $content,
        ];
    }
}
