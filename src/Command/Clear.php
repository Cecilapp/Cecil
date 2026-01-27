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
        $this->removeOutputDir($output);
        $this->removeTmpDir($output);
        // deletes all cache files
        $command = $this->getApplication()->find('cache:clear');
        $command->run($input, $output);

        return Command::SUCCESS;;
    }

    /**
     * Removes the output directory.
     */
    private function removeOutputDir(OutputInterface $output): void
    {
        $outputDir = (string) $this->getBuilder()->getConfig()->get('output.dir');
        // if custom output directory
        if (Util\File::getFS()->exists(Util::joinFile($this->getPath(), self::TMP_DIR, 'output'))) {
            $outputDir = Util\File::fileGetContents(Util::joinFile($this->getPath(), self::TMP_DIR, 'output'));
        }
        if ($outputDir === false || !Util\File::getFS()->exists(Util::joinFile($this->getPath(), $outputDir))) {
            $output->writeln('<info>No output directory.</info>');
            return;
        }
        $output->writeln('Removing output directory...');
        $output->writeln(
            \sprintf('<comment>Path: %s</comment>', Util::joinFile($this->getPath(), $outputDir)),
            OutputInterface::VERBOSITY_VERBOSE
        );
        Util\File::getFS()->remove(Util::joinFile($this->getPath(), $outputDir));
        $output->writeln('<info>Output directory removed.</info>');
    }

    /**
     * Removes temporary directory.
     */
    private function removeTmpDir(OutputInterface $output): void
    {
        if (!Util\File::getFS()->exists(Util::joinFile($this->getPath(), self::TMP_DIR))) {
            $output->writeln('<info>No temporary directory.</info>');
            return;
        }
        $output->writeln('Removing temporary directory...');
        $output->writeln(
            \sprintf('<comment>Path: %s</comment>', Util::joinFile($this->getPath(), self::TMP_DIR)),
            OutputInterface::VERBOSITY_VERBOSE
        );
        Util\File::getFS()->remove(Util::joinFile($this->getPath(), self::TMP_DIR));
        $output->writeln('<info>Temporary directory removed.</info>');
    }
}
