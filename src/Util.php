<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class Util
{
    /**
     * Symfony\Component\Filesystem.
     *
     * @var Filesystem
     */
    protected static $fs;

    /**
     * Return Symfony\Component\Filesystem instance.
     *
     * @return Filesystem
     */
    public static function getFS()
    {
        if (!self::$fs instanceof Filesystem) {
            self::$fs = new Filesystem();
        }

        return self::$fs;
    }

    /**
     * Runs a Git command on the repository.
     *
     * @param string $command The command.
     *
     * @throws \RuntimeException If the command failed.
     *
     * @return string The trimmed output from the command.
     */
    public static function runGitCommand($command)
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

    /**
     * Sort array by 'date' item.
     *
     * @param $a
     * @param $b
     *
     * @return int
     */
    public static function sortByDate($a, $b)
    {
        if (!isset($a['date'])) {
            return -1;
        }
        if (!isset($b['date'])) {
            return 1;
        }
        if ($a['date'] == $b['date']) {
            return 0;
        }

        return ($a['date'] > $b['date']) ? -1 : 1;
    }

    /**
     * Checks if a date is valid.
     *
     * @param string $date
     * @param string $format
     *
     * @return bool
     */
    public static function isValidDate(string $date, string $format = 'Y-m-d'): bool
    {
        $d = \DateTime::createFromFormat($format, $date);

        return $d && $d->format($format) === $date;
    }
}
