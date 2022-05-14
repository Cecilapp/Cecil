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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Removes cache files.
 */
class CacheClear extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('cache:clear')
            ->setDescription('Removes all caches')
            ->setDefinition(
                new InputDefinition([
                    new InputArgument('path', InputArgument::OPTIONAL, 'Use the given path as working directory'),
                ])
            )
            ->setHelp('Removes all cached files');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->fs->exists($this->getBuilder()->getConfig()->getCachePath())) {
            $output->writeln('<info>No cache.</info>');

            return 0;
        }
        $output->writeln('Removing cache directory...');
        $output->writeln(
            \sprintf('<comment>Path %s</comment>', $this->getBuilder()->getConfig()->getCachePath()),
            OutputInterface::VERBOSITY_VERBOSE
        );
        $this->fs->remove($this->getBuilder()->getConfig()->getCachePath());
        $output->writeln('<info>Cache is clear.</info>');

        return 0;
    }
}
