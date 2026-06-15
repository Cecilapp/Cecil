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

use Cecil\Builder;
use Cecil\Util;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * ClearTmp command.
 *
 * This command removes the temporary directory.
 */
class ClearTmp extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('clear:temporary')
            ->setAliases(['clear:tmp'])
            ->setDescription('Removes temporary directory')
            ->setDefinition([
                new InputArgument('path', InputArgument::OPTIONAL, 'Use the given path as working directory'),
            ])
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</> command removes temporary directory.
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title('Removing temporary directory');
        if (!Util\File::getFS()->exists(Util::joinFile($this->getPath(), Builder::TMP_DIR))) {
            $this->io->success('No temporary directory.');

            return Command::SUCCESS;
        }
        $output->writeln(
            \sprintf('<comment>Path: %s</comment>', Util::joinFile($this->getPath(), Builder::TMP_DIR)),
            OutputInterface::VERBOSITY_VERBOSE
        );
        Util\File::getFS()->remove(Util::joinFile($this->getPath(), Builder::TMP_DIR));
        $this->io->success('Temporary directory removed.');

        return Command::SUCCESS;
    }
}
