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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Clear command.
 *
 * This command removes all generated files, including the output directory, temporary directory, and cache files.
 * It is useful for cleaning up the build environment before starting a new build or to free up space.
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
            ->setDescription('Removes all generated files')
            ->setDefinition([
                new InputArgument('path', InputArgument::OPTIONAL, 'Use the given path as working directory'),
            ])
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</> command removes output directory, temporary directory and cache files.
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->getApplication()->find('clear:output')->run($input, $output);
        $this->getApplication()->find('clear:tmp')->run($input, $output);
        $this->getApplication()->find('cache:clear')->run($input, $output);

        return Command::SUCCESS;
    }
}
