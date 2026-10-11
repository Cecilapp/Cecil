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

use Cecil\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Handles the `--fail-on` option of audit commands, to return a failure exit code
 * when findings of a given level (or more severe) are found.
 */
trait FailOnTrait
{
    /** Findings levels, from the most to the least severe */
    private const array FAIL_ON_LEVELS = ['bad', 'ok', 'feedback'];

    /**
     * Returns the `--fail-on` option definition.
     */
    protected function getFailOnOption(): InputOption
    {
        return new InputOption(
            'fail-on',
            null,
            InputOption::VALUE_REQUIRED,
            'Return a failure exit code when findings of this level or higher are found: bad (default), ok, feedback or never',
            'bad'
        );
    }

    /**
     * Returns the validated `--fail-on` option value.
     *
     * @throws RuntimeException
     */
    protected function getFailOn(InputInterface $input): string
    {
        $failOn = (string) $input->getOption('fail-on');
        if ($failOn !== 'never' && !\in_array($failOn, self::FAIL_ON_LEVELS, true)) {
            throw new RuntimeException(\sprintf('Invalid "--fail-on" value "%s" (expected: %s or never).', $failOn, implode(', ', self::FAIL_ON_LEVELS)));
        }

        return $failOn;
    }

    /**
     * Should the command fail, according to the findings counts and the `--fail-on` level?
     *
     * @param array{bad_count: int, ok_count: int, feedback_count: int} $summary
     */
    protected function shouldFail(array $summary, string $failOn): bool
    {
        if ($failOn === 'never') {
            return false;
        }
        foreach (self::FAIL_ON_LEVELS as $level) {
            if ($summary[$level . '_count'] > 0) {
                return true;
            }
            if ($level === $failOn) {
                break;
            }
        }

        return false;
    }
}
