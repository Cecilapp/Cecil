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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CacheClearTranslations command.
 *
 * This command removes cached translations files from the translations cache directory.
 * It is useful for clearing outdated or unnecessary translations that may have been cached during previous builds.
 */
class CacheClearTranslations extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('cache:clear:translations')
            ->setDescription('Removes translations cache')
            ->setDefinition([
                new InputArgument('path', InputArgument::OPTIONAL, 'Use the given path as working directory'),
            ])
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</> command removes cached translations files.
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!Util\File::getFS()->exists($this->getBuilder()->getConfig()->getCacheTranslationsPath())) {
            $output->writeln('<info>No translations cache.</info>');

            return 0;
        }
        $output->writeln('Removing translations cache directory...');
        $output->writeln(
            \sprintf('<comment>Path: %s</comment>', $this->getBuilder()->getConfig()->getCacheTranslationsPath()),
            OutputInterface::VERBOSITY_VERBOSE
        );
        Util\File::getFS()->remove($this->getBuilder()->getConfig()->getCacheTranslationsPath());
        $output->writeln('<info>Translations cache is clear.</info>');

        return 0;
    }
}
