<?php
/**
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Util;

use Symfony\Component\Process\Process;

class Git
{
    /**
     * Runs a Git command on the repository.
     *
     * @param string $command The command.
     *
     * @throws \RuntimeException If the command failed.
     *
     * @return string The trimmed output from the command.
     */
    public static function runGitCommand(string $command): string
    {
        try {
            $process = new Process($command, __DIR__);
            if (0 === $process->run()) {
                return trim($process->getOutput());
            }

            throw new \RuntimeException(
                sprintf(
                    'The tag or commit hash could not be retrieved from "%s": %s',
                    __DIR__,
                    $process->getErrorOutput()
                )
            );
        } catch (\RuntimeException $exception) {
            throw new \RuntimeException('Process error');
        }
    }
}
