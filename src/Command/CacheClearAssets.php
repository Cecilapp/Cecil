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
 * Removes assets cache files.
 */
class CacheClearAssets extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('cache:clear:assets')
            ->setDescription('Removes assets cache')
            ->setDefinition(
                new InputDefinition([
                    new InputArgument('path', InputArgument::OPTIONAL, 'Use the given path as working directory'),
                ])
            )
            ->setHelp('Removes cached assets files');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!Util\File::getFS()->exists($this->getBuilder()->getConfig()->getCacheAssetsPath())) {
            $output->writeln('<info>No assets cache.</info>');

            return 0;
        }
        $output->writeln('Removing assets cache directory...');
        $output->writeln(
            \sprintf('<comment>Path %s</comment>', $this->getBuilder()->getConfig()->getCacheAssetsPath()),
            OutputInterface::VERBOSITY_VERBOSE
        );
        Util\File::getFS()->remove($this->getBuilder()->getConfig()->getCacheAssetsPath());
        $output->writeln('<info>Assets cache is clear.</info>');

        return 0;
    }
}
