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

namespace Cecil\Logger;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Console logger with progress bar support.
 */
class ProgressConsoleLogger extends ConsoleLogger
{
    private ProgressBar $progressBar;
    private int $lastAdvancedStep = 0;

    public function __construct(OutputInterface $output, ProgressBar $progressBar, array $verbosityLevelMap = [], array $formatLevelMap = [])
    {
        parent::__construct($output, $verbosityLevelMap, $formatLevelMap);

        $this->progressBar = $progressBar;
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        if (isset($context['step']) && \is_array($context['step'])) {
            $step = (int) ($context['step'][0] ?? 0);
            $stepsTotal = (int) ($context['step'][1] ?? 0);
            if (0 < $stepsTotal && 0 === $this->progressBar->getMaxSteps()) {
                $this->progressBar->setMaxSteps($stepsTotal);
            }
            if ($step > $this->lastAdvancedStep) {
                $this->progressBar->setMessage((string) $message);
                $this->progressBar->setProgress($step);
                $this->lastAdvancedStep = $step;
            }
        }

$shouldWrite = isset($this->verbosityLevelMap[$level]) && $this->verbosityLevelMap[$level] <= $this->output->getVerbosity();
if ($shouldWrite) {
    $this->progressBar->clear();
}

parent::log($level, $message, $context);

if ($shouldWrite) {
    $this->progressBar->display();
}
    }
}
