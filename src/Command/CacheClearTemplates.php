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

use Cecil\Util;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Removes templates cache files.
 */
class CacheClearTemplates extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('cache:clear:templates')
            ->setDescription('Removes templates cache')
            ->setDefinition(
                new InputDefinition([
                    new InputArgument('path', InputArgument::OPTIONAL, 'Use the given path as working directory'),
                ])
            )
            ->setHelp('Removes cached templates files');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->fs->exists(Util::joinFile($this->getBuilder()->getConfig()->getCachePath(), 'templates'))) {
            $output->writeln('<info>No templates cache.</info>');

            return 0;
        }
        $output->writeln('Removing templates cache directory...');
        $output->writeln(
            \sprintf('<comment>Path %s</comment>', Util::joinFile($this->getBuilder()->getConfig()->getCachePath(), 'templates')),
            OutputInterface::VERBOSITY_VERBOSE
        );
        $this->fs->remove(Util::joinFile($this->getBuilder()->getConfig()->getCachePath(), 'templates'));
        $output->writeln('<info>Templates cache is clear.</info>');

        return 0;
    }
}
