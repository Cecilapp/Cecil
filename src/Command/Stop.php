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

use Cecil\Exception\RuntimeException;
use Cecil\Util;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

/**
 * Stop command.
 *
 * This command stops a background server started with `serve --background`.
 */
class Stop extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('stop')
            ->setDescription('Stops the background server')
            ->setDefinition([
                new InputArgument('path', InputArgument::OPTIONAL, 'Use the given path as working directory'),
            ])
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</> command stops a background server previously started with <info>serve --background</>.

  <info>%command.full_name%</>
  <info>%command.full_name% path/to/the/working/directory</>
EOF
            );
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pidFile = Util::joinFile($this->getPath(), self::PID_FILE);

        if (!\is_file($pidFile)) {
            $output->writeln('<error>No background server found (PID file missing).</error>');

            return Command::FAILURE;
        }

        $pid = (int) \file_get_contents($pidFile);
        if ($pid <= 0) {
            $output->writeln('<error>Invalid PID file.</error>');
            try {
                Util\File::getFS()->remove($pidFile);
            } catch (IOExceptionInterface $e) {
                throw new RuntimeException($e->getMessage());
            }

            return Command::FAILURE;
        }

        // kill the server process
        if (Util\Platform::isWindows()) {
            \exec(\sprintf('taskkill /F /PID %d 2>NUL', $pid));
        } else {
            \exec(\sprintf('kill %d 2>/dev/null', $pid));
        }

        // clean up PID file
        Util\File::getFS()->remove($pidFile);

        // remove server output directory
        try {
            Util\File::getFS()->remove(Util::joinFile($this->getPath(), self::SERVE_OUTPUT));
        } catch (IOExceptionInterface $e) {
            throw new RuntimeException($e->getMessage());
        }

        $output->writeln('<info>Server stopped.</info>');

        return Command::SUCCESS;
    }
}
