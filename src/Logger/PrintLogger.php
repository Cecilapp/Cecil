<?php declare(strict_types=1);

/*
 * This file is part of Cecil.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Logger;

use Cecil\Builder;
use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;

class PrintLogger extends AbstractLogger
{
    /** @var int */
    protected $printLevelMax = null;

    /** @var array */
    protected $verbosityLevelMap = [
        LogLevel::EMERGENCY => Builder::VERBOSITY_NORMAL,
        LogLevel::ALERT     => Builder::VERBOSITY_NORMAL,
        LogLevel::CRITICAL  => Builder::VERBOSITY_NORMAL,
        LogLevel::ERROR     => Builder::VERBOSITY_NORMAL,
        LogLevel::WARNING   => Builder::VERBOSITY_NORMAL,
        LogLevel::NOTICE    => Builder::VERBOSITY_NORMAL,
        LogLevel::INFO      => Builder::VERBOSITY_VERBOSE,
        LogLevel::DEBUG     => Builder::VERBOSITY_DEBUG,
    ];

    /**
     * Print only the $printLevelMax.
     */
    public function __construct(int $printLevelMax = null)
    {
        $this->printLevelMax = $printLevelMax;
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function log($level, $message, array $context = [])
    {
        if (!isset($this->verbosityLevelMap[$level])) {
            throw new InvalidArgumentException(\sprintf('The log level "%s" does not exist.', $level));
        }

        if ($this->printLevelMax !== null && $this->verbosityLevelMap[$level] > $this->printLevelMax) {
            return;
        }

        $level = $level != LogLevel::INFO ? "[$level] " : '';

        if (isset($context['progress'])) {
            printf(
                "%s%s (%s/%s)\n",
                $level,
                $this->interpolate($message, $context),
                $context['progress'][0],
                $context['progress'][1]
            );

            return;
        }

        printf("%s%s\n", $level, $this->interpolate($message, $context));
    }

    /**
     * Interpolates context values into the message placeholders.
     *
     * @author PHP Framework Interoperability Group
     */
    protected function interpolate(string $message, array $context): string
    {
        if (false === strpos($message, '{')) {
            return $message;
        }

        $replacements = [];
        foreach ($context as $key => $val) {
            if (null === $val || is_scalar($val) || (\is_object($val) && method_exists($val, '__toString'))) {
                $replacements["{{$key}}"] = $val;
            } elseif ($val instanceof \DateTimeInterface) {
                $replacements["{{$key}}"] = $val->format(\DateTime::RFC3339);
            } elseif (\is_object($val)) {
                $replacements["{{$key}}"] = '[object '.\get_class($val).']';
            } else {
                $replacements["{{$key}}"] = '['.\gettype($val).']';
            }
        }

        return strtr($message, $replacements);
    }

    /**
     * Format expression to string.
     */
    public static function format($expression): string
    {
        return str_replace(["\n", ' '], '', var_export($expression, true));
    }
}
