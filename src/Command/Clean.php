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
class Clean extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('clean')
            ->setDescription('Removes generated files')
            ->setDefinition(
                new InputDefinition([
                    new InputArgument('path', InputArgument::OPTIONAL, 'Use the given path as working directory'),
                ])
            )
            ->setHelp('Removes generated and temporary files');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $doSomething = false;

        // deletes output dir
        $outputDir = (string) $this->getBuilder()->getConfig()->get('output.dir');
        if ($this->fs->exists(Util::joinFile($this->getPath(), self::TMP_DIR, 'output'))) {
            $outputDir = Util\File::fileGetContents(Util::joinFile($this->getPath(), self::TMP_DIR, 'output'));
        }
        if ($outputDir !== false && $this->fs->exists(Util::joinFile($this->getPath(), $outputDir))) {
            $this->fs->remove(Util::joinFile($this->getPath(), $outputDir));
            $output->writeln('Removing output directory...');
            $output->writeln(
                sprintf('<comment>> %s</comment>', Util::joinFile($this->getPath(), $outputDir)),
                OutputInterface::VERBOSITY_VERBOSE
            );
            $doSomething = true;
        }
        // deletes local server temporary files
        if ($this->fs->exists($this->getPath().'/'.self::TMP_DIR)) {
            $this->fs->remove($this->getPath().'/'.self::TMP_DIR);
            $output->writeln('Removing temporary directory...');
            $output->writeln(
                sprintf('<comment>> %s</comment>', Util::joinFile($this->getPath(), self::TMP_DIR)),
                OutputInterface::VERBOSITY_VERBOSE
            );
            $doSomething = true;
        }
        // deletes cache directory
        if ($this->fs->exists($this->builder->getConfig()->getCachePath())) {
            $this->fs->remove($this->builder->getConfig()->getCachePath());
            $output->writeln('Removing cache directory...');
            $output->writeln(
                sprintf('<comment>> %s</comment>', $this->builder->getConfig()->getCachePath()),
                OutputInterface::VERBOSITY_VERBOSE
            );
            $doSomething = true;
        }

        if ($doSomething === false) {
            $output->writeln('<comment>Nothing to do.</comment>');

            return 0;
        }

        $output->writeln('<info>All is clean!</info>');

        return 0;
    }
}
