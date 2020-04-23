<?php
/**
 * This file is part of the Cecil/Cecil package.
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
    protected $printLevel = null;
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
     * @var int Print only this maximum level.
     */
    public function __construct(int $printLevel = null)
    {
        $this->printLevel = $printLevel;
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function log($level, $message, array $context = [])
    {
        if (!isset($this->verbosityLevelMap[$level])) {
            throw new InvalidArgumentException(sprintf('The log level "%s" does not exist.', $level));
        }

        if ($this->printLevel !== null && $this->verbosityLevelMap[$level] > $this->printLevel) {
            return;
        }

        if (array_key_exists('progress', $context)) {
            printf(
                " (%s/%s) %s\n",
                $context['progress'][0],
                $context['progress'][1],
                $this->interpolate($message, $context)
            );

            return;
        }

        printf("[%s] %s\n", $level, $this->interpolate($message, $context));
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
}
