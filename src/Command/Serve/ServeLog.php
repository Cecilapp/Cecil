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

namespace Cecil\Command\Serve;

use Cecil\Command\AbstractCommand;
use Cecil\Builder;
use Cecil\Util;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Serve Log command.
 *
 * This command displays a combined log of the server and errors log files.
 */
class ServeLog extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('serve:log')
            ->setDescription('Shows combined server and error logs')
            ->setDefinition([
                new InputArgument('path', InputArgument::OPTIONAL, 'Use the given path as working directory'),
                new InputOption('lines', 'l', InputOption::VALUE_REQUIRED, 'Number of entries to display', 25),
            ])
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</> command displays entries from combined server and error logs.

  <info>%command.full_name%</>
  <info>%command.full_name% path/to/the/working/directory</>
  <info>%command.full_name% --lines=100</>
  <info>%command.full_name% -l 100</>
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $this->getPath();
        $logsDir = Util::joinFile($path, Builder::TMP_DIR);

        // Check if the logs directory exists
        if (!is_dir($logsDir)) {
            $output->writeln('<error>No server logs found. Make sure you have run "serve" command.</error>');

            return Command::FAILURE;
        }

        // Read log files
        $errorsLogFile = Util::joinFile($logsDir, 'errors.log');
        $serverLogFile = Util::joinFile($logsDir, 'server.log');

        $entries = [];

        // Read errors.log
        if (is_file($errorsLogFile)) {
            $lines = file($errorsLogFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines !== false) {
                foreach ($lines as $line) {
                    $entries[] = $line;
                }
            }
        }

        // Read server.log
        if (is_file($serverLogFile)) {
            $lines = file($serverLogFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines !== false) {
                foreach ($lines as $line) {
                    $entries[] = $line;
                }
            }
        }

        if (empty($entries)) {
            $output->writeln('<info>No log entries found.</info>');

            return Command::SUCCESS;
        }

        // Sort entries by timestamp
        usort($entries, function (string $a, string $b): int {
            $timestampA = $this->extractTimestamp($a);
            $timestampB = $this->extractTimestamp($b);

            return strtotime($timestampA ?? '0') <=> strtotime($timestampB ?? '0');
        });

        // Get the number of lines to display
        $numLines = (int) $input->getOption('lines');
        if ($numLines < 1) {
            $output->writeln('<error>The number of lines must be greater than 0.</error>');

            return Command::FAILURE;
        }

        // Keep only the last N entries
        $entries = \array_slice($entries, -$numLines);

        // Display entries
        foreach ($entries as $entry) {
            $output->writeln($entry);
        }

        return Command::SUCCESS;
    }

    /**
     * Extract timestamp from a log entry.
     *
     * @param string $entry Log entry string
     *
     * @return string|null Timestamp string or null if not found
     */
    private function extractTimestamp(string $entry): ?string
    {
        // Match pattern: [Day Mon DD HH:MM:SS YYYY]
        if (preg_match('/\[([A-Za-z]{3}\s+[A-Za-z]{3}\s+\d{1,2}\s+\d{1,2}:\d{2}:\d{2}\s+\d{4})\]/', $entry, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
