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

namespace Cecil\Command\Doctor;

use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Terminal;

/**
 * Renders audit commands findings as tables grouped by page, with columns sized
 * to the terminal width (and a vertical display for narrow terminals).
 */
trait FindingsTableTrait
{
    /** Below this terminal width, findings are displayed vertically */
    private const int FINDINGS_VERTICAL_THRESHOLD = 100;

    private const int FINDINGS_MIN_COLUMN_WIDTH = 20;

    /**
     * Renders findings grouped by page (one table per page).
     *
     * Each column is defined by a header, a cell callback and an optional ratio:
     * - without ratio, the column is as wide as its content;
     * - with a ratio, the column shares the remaining width and its content is wrapped.
     * The cell callback receives the finding and the width to wrap to: null for
     * horizontal tables (wrapped with the column max width), an int for vertical
     * tables (which only indent multi-line values).
     *
     * @param array<int, array<string, string>>                                                            $findings
     * @param array<int, array{header: string, cell: \Closure(array<string, string>, ?int): string, ratio?: float}> $columns
     */
    protected function renderFindings(OutputInterface $output, array $findings, array $columns): void
    {
        $terminalWidth = (new Terminal())->getWidth();
        $formatter = $output->getFormatter();
        $headers = array_column($columns, 'header');

        // groups findings by page, to avoid repeating the page in each row
        $findingsByPage = [];
        foreach ($findings as $finding) {
            $findingsByPage[$finding['page']][] = $finding;
        }

        $widths = [];
        $vertical = $terminalWidth < self::FINDINGS_VERTICAL_THRESHOLD;
        if ($vertical) {
            // "│ " + longest header + ": " + value + " │"
            $maxHeaderWidth = max(0, ...array_map(static fn (string $header): int => Helper::width($header), $headers));
            $valueWidth = max(self::FINDINGS_MIN_COLUMN_WIDTH, $terminalWidth - $maxHeaderWidth - 6);
            foreach ($columns as $i => $column) {
                $widths[$i] = isset($column['ratio']) ? $valueWidth : null;
            }
        } else {
            // vertical borders + 1 space of padding on each side of the columns
            $available = $terminalWidth - (\count($columns) + 1) - 2 * \count($columns);
            $ratios = [];
            foreach ($columns as $i => $column) {
                if (isset($column['ratio'])) {
                    $ratios[$i] = $column['ratio'];
                    continue;
                }
                $width = Helper::width($column['header']);
                foreach ($findings as $finding) {
                    $width = max($width, Helper::width(Helper::removeDecoration($formatter, ($column['cell'])($finding, null))));
                }
                $available -= $width;
            }
            // the last flexible column takes the remaining width
            $remaining = $available;
            $lastFlexible = array_key_last($ratios);
            foreach ($ratios as $i => $ratio) {
                $width = $i === $lastFlexible ? $remaining : (int) floor($available * $ratio);
                $widths[$i] = max(self::FINDINGS_MIN_COLUMN_WIDTH, $width);
                $remaining -= $widths[$i];
            }
        }

        foreach ($findingsByPage as $page => $pageFindings) {
            // section title without the trailing blank line of SymfonyStyle::section()
            $pageLabel = OutputFormatter::escape($this->truncateLabel((string) $page, $terminalWidth));
            $output->writeln([
                \sprintf('<comment>%s</>', $pageLabel),
                \sprintf('<comment>%s</>', str_repeat('-', Helper::width(Helper::removeDecoration($formatter, $pageLabel)))),
            ]);

            $rows = [];
            foreach ($pageFindings as $finding) {
                $row = [];
                foreach ($columns as $i => $column) {
                    $row[] = ($column['cell'])($finding, $vertical ? ($widths[$i] ?? null) : null);
                }
                $rows[] = $row;
            }

            $table = new Table($output);
            $table
                ->setHeaders($headers)
                ->setRows($rows)
                ->setVertical($vertical)
            ;
            if (!$vertical) {
                foreach ($widths as $i => $width) {
                    $table->setColumnMaxWidth($i, (int) $width);
                }
            }
            $table->setStyle('box')->render();
        }
    }

    /**
     * Wraps a plain text value to the given width, if any.
     */
    protected function wrapFindingValue(string $value, ?int $width): string
    {
        return $width !== null ? wordwrap($value, $width, "\n", true) : $value;
    }

    /**
     * Formats a finding issue: its level followed by its kind (type, check, etc.).
     */
    protected function formatFindingIssue(string $level, string $kind): string
    {
        $levelLabel = match ($level) {
            'bad' => '<error>Bad</error>',
            'ok' => '<comment>OK</comment>',
            default => '<info>Feedback</info>',
        };

        return \sprintf('%s · %s', $levelLabel, $kind);
    }

    /**
     * Truncates a label in its middle, to keep its beginning and its end.
     */
    protected function truncateLabel(string $label, int $maxLength): string
    {
        if (mb_strlen($label) <= $maxLength) {
            return $label;
        }

        $ellipsis = '...';
        $keepLength = $maxLength - \strlen($ellipsis);
        $startLength = (int) floor($keepLength / 2);
        $endLength = $keepLength - $startLength;

        return mb_substr($label, 0, $startLength) . $ellipsis . mb_substr($label, -$endLength);
    }
}
