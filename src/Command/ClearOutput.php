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
 * ClearOutput command.
 *
 * This command removes the output directory.
 */
class ClearOutput extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('clear:output')
            ->setDescription('Removes output directory')
            ->setDefinition([
                new InputArgument('path', InputArgument::OPTIONAL, 'Use the given path as working directory'),
            ])
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</> command removes output directory.
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title('Removing output directory');
        $outputDir = (string) $this->getBuilder()->getConfig()->get('output.dir');
        // if custom output directory
        if (Util\File::getFS()->exists(Util::joinFile($this->getPath(), Builder::TMP_DIR, 'output'))) {
            $outputDir = Util\File::fileGetContents(Util::joinFile($this->getPath(), Builder::TMP_DIR, 'output'));
        }
        if ($outputDir === false || !Util\File::getFS()->exists(Util::joinFile($this->getPath(), $outputDir))) {
            $this->io->success('No output directory.');

            return Command::SUCCESS;
        }
        $output->writeln(
            \sprintf('<comment>Path: %s</comment>', Util::joinFile($this->getPath(), $outputDir)),
            OutputInterface::VERBOSITY_VERBOSE
        );
        Util\File::getFS()->remove(Util::joinFile($this->getPath(), $outputDir));
        $this->io->success('Output directory removed.');

        return Command::SUCCESS;
    }
}
