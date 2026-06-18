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

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Yosymfony\ResourceWatcher\ResourceWatcherResult;

/**
 * Executes incremental build strategy and runs the required build subprocesses.
 */
class IncrementalBuildExecutor
{
    public function __construct(
        private readonly IncrementalBuildResolver $resolver,
    ) {
    }

    /**
     * @param callable(?string): Process $createBuildProcess
     * @param callable(string, string): void $processOutputCallback
     * @param callable(): void $flushBuildOutput
     * @param callable(): void $onBuildSuccess
     */
    public function execute(
        ResourceWatcherResult $watcher,
        OutputInterface $output,
        callable $createBuildProcess,
        callable $processOutputCallback,
        callable $flushBuildOutput,
        callable $onBuildSuccess
    ): void {
        $pages = $this->resolver->resolve($watcher);

        // null means a full rebuild is required
        if ($pages === null) {
            $output->writeln('<comment>Incremental: full rebuild required</comment>');
            $fullBuildProcess = $createBuildProcess(null);
            $fullBuildProcess->run($processOutputCallback);
            $flushBuildOutput();
            if ($fullBuildProcess->isSuccessful()) {
                $onBuildSuccess();
            }

            return;
        }

        $allSuccessful = true;
        if (\count($pages) === 0) {
            $output->writeln('<comment>Incremental: no impacted pages</comment>');
        }
        foreach ($pages as $page) {
            $output->writeln(\sprintf('<comment>Incremental: building page "%s"</comment>', $page));
            $process = $createBuildProcess($page);
            $process->run($processOutputCallback);
            $flushBuildOutput();
            if (!$process->isSuccessful()) {
                $allSuccessful = false;
            }
        }

        if ($allSuccessful) {
            $onBuildSuccess();
        } else {
        }
    }
}
