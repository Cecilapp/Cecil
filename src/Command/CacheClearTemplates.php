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
use Symfony\Component\Console\Input\InputOption;

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
            ->setDefinition([
                new InputArgument('path', InputArgument::OPTIONAL, 'Use the given path as working directory'),
                new InputOption('fragments', null, InputOption::VALUE_NONE, 'Remove fragments cache only'),
            ])
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</> command removes cached templates files.

To remove all templates cache, run:

  <info>%command.full_name%</>

To remove templates fragments cache only, run:

  <info>%command.full_name% --fragments</>
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cacheTemplatesPath = $this->getBuilder()->getConfig()->getCacheTemplatesPath();
        if (!Util\File::getFS()->exists($cacheTemplatesPath)) {
            $output->writeln('<info>No templates cache.</info>');

            return 0;
        }
        if ($input->getOption('fragments')) {
            $output->writeln('Removing templates fragments cache directory...');
            $cacheFragmentsPath = Util::joinFile($cacheTemplatesPath, '_fragments');
            $output->writeln(\sprintf('<comment>Path: %s</comment>', $cacheFragmentsPath), OutputInterface::VERBOSITY_VERBOSE);
            Util\File::getFS()->remove($cacheFragmentsPath);
            $output->writeln('<info>Templates fragments cache is clear.</info>');

            return 0;
        }
        $output->writeln('Removing templates cache directory...');
        $output->writeln(\sprintf('<comment>Path: %s</comment>', $cacheTemplatesPath), OutputInterface::VERBOSITY_VERBOSE);
        Util\File::getFS()->remove($cacheTemplatesPath);
        $output->writeln('<info>Templates cache is clear.</info>');

        return 0;
    }
}
