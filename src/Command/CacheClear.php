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

use Cecil\Util;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CacheClear command.
 *
 * This command removes all cached files from the cache directory.
 * It can be used to clear the cache before a new build or to free up space.
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
            ->setDescription('Removes all cache files')
            ->setDefinition([
                new InputArgument('path', InputArgument::OPTIONAL, 'Use the given path as working directory'),
            ])
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</> command removes all cached files.
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!Util\File::getFS()->exists($this->getBuilder()->getConfig()->getCachePath())) {
            $output->writeln('<info>No cache.</info>');

            return 0;
        }
        $output->writeln('Removing cache directory...');
        $output->writeln(
            \sprintf('<comment>Path: %s</comment>', $this->getBuilder()->getConfig()->getCachePath()),
            OutputInterface::VERBOSITY_VERBOSE
        );
        Util\File::getFS()->remove($this->getBuilder()->getConfig()->getCachePath());
        $output->writeln('<info>Cache is clear.</info>');

        return 0;
    }
}
