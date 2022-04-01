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
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Removes generated and temporary files.
 */
class Clear extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('clear')
            ->setDescription('Removes generated files')
            ->setAliases(['clean'])
            ->setDefinition(
                new InputDefinition([
                    new InputArgument('path', InputArgument::OPTIONAL, 'Use the given path as working directory'),
                ])
            )
            ->setHelp('Removes generated, temporary and cache files');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->removeOutputDir($output);
        $this->removeTmpDir($output);

        // deletes cache
        $command = $this->getApplication()->find('cache:clear');
        $command->run($input, $output);

        return 0;
    }

    /**
     * Deletes output directory.
     */
    private function removeOutputDir(OutputInterface $output): void
    {
        $outputDir = (string) $this->getBuilder()->getConfig()->get('output.dir');
        if ($this->fs->exists(Util::joinFile($this->getPath(), self::TMP_DIR, 'output'))) {
            $outputDir = Util\File::fileGetContents(Util::joinFile($this->getPath(), self::TMP_DIR, 'output'));
        }

        if ($outputDir === false || !$this->fs->exists(Util::joinFile($this->getPath(), $outputDir))) {
            $output->writeln('<info>No output directory.</info>');

            return;
        }

        $output->writeln('Removing output directory...');
        $output->writeln(
            sprintf('<comment>Path: %s</comment>', Util::joinFile($this->getPath(), $outputDir)),
            OutputInterface::VERBOSITY_VERBOSE
        );
        $this->fs->remove(Util::joinFile($this->getPath(), $outputDir));
        $output->writeln('<info>Output directory is clear.</info>');
    }

    /**
     * Deletes local server temporary files.
     */
    private function removeTmpDir(OutputInterface $output): void
    {
        if (!$this->fs->exists(Util::joinFile($this->getPath(), self::TMP_DIR))) {
            $output->writeln('<info>No temporary files.</info>');

            return;
        }

        $output->writeln('Removing temporary directory...');
        $output->writeln(
            sprintf('<comment>Path: %s</comment>', Util::joinFile($this->getPath(), self::TMP_DIR)),
            OutputInterface::VERBOSITY_VERBOSE
        );
        $this->fs->remove(Util::joinFile($this->getPath(), self::TMP_DIR));
        $output->writeln('<info>Temporary directory is clear.</info>');
    }
}
