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

use Cecil\Util;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Clean extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('clean')
            ->setDescription('Remove the output directory')
            ->setDefinition(
                new InputDefinition([
                    new InputArgument('path', InputArgument::OPTIONAL, 'Use the given path as working directory'),
                ])
            )
            ->setHelp('Remove the output directory and temporary files.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $doSomething = false;

        // delete output dir
        $outputDir = (string) $this->getBuilder($output)->getConfig()->get('output.dir');
        if ($this->fs->exists($this->getPath().'/'.self::TMP_DIR.'/output')) {
            $outputDir = file_get_contents($this->getPath().'/'.self::TMP_DIR.'/output');
        }
        if ($this->fs->exists($this->getPath().'/'.$outputDir)) {
            $this->fs->remove($this->getPath().'/'.$outputDir);
            $output->writeln('Output directory removed.');
            $output->writeln(
                sprintf('> %s', Util::joinFile([$this->getPath(), $outputDir])),
                OutputInterface::VERBOSITY_VERBOSE
            );
            $doSomething = true;
        }
        // delete local server temp files
        if ($this->fs->exists($this->getPath().'/'.self::TMP_DIR)) {
            $this->fs->remove($this->getPath().'/'.self::TMP_DIR);
            $output->writeln('Temporary directory deleted.');
            $output->writeln(
                sprintf('> %s', Util::joinFile([$this->getPath(), self::TMP_DIR])),
                OutputInterface::VERBOSITY_VERBOSE
            );
            $doSomething = true;
        }
        // delete cache directory
        if ($this->fs->exists($this->builder->getConfig()->getCachePath())) {
            $this->fs->remove($this->builder->getConfig()->getCachePath());
            $output->writeln('Cache directory deleted.');
            $output->writeln(
                sprintf('> %s', $this->builder->getConfig()->getCachePath()),
                OutputInterface::VERBOSITY_VERBOSE
            );
            $doSomething = true;
        }

        if ($doSomething === false) {
            $output->writeln('Nothing to do.');
        }

        return 0;
    }
}
