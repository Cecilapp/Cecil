<?php

/*
 * This file is part of Cecil.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Logger;

use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleLogger extends PrintLogger
{
    const ERROR = 'error';
    const WARNING = 'comment';
    const NOTICE = 'info';
    const INFO = 'text';
    const DEBUG = 'debug';

    protected $output;

    protected $verbosityLevelMap = [
        LogLevel::EMERGENCY => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::ALERT     => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::CRITICAL  => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::ERROR     => OutputInterface::VERBOSITY_NORMAL,
        LogLevel::WARNING   => OutputInterface::VERBOSITY_VERY_VERBOSE,
        LogLevel::NOTICE    => OutputInterface::VERBOSITY_VERBOSE,
        LogLevel::INFO      => OutputInterface::VERBOSITY_VERY_VERBOSE,
        LogLevel::DEBUG     => OutputInterface::VERBOSITY_DEBUG,
    ];

    protected $formatLevelMap = [
        LogLevel::EMERGENCY => self::ERROR,
        LogLevel::ALERT     => self::ERROR,
        LogLevel::CRITICAL  => self::ERROR,
        LogLevel::ERROR     => self::ERROR,
        LogLevel::WARNING   => self::WARNING,
        LogLevel::NOTICE    => self::NOTICE,
        LogLevel::INFO      => self::INFO,
        LogLevel::DEBUG     => self::DEBUG,
    ];

    public function __construct(OutputInterface $output, array $verbosityLevelMap = [], array $formatLevelMap = [])
    {
        $this->output = $output;
        $this->verbosityLevelMap = $verbosityLevelMap + $this->verbosityLevelMap;
        $this->formatLevelMap = $formatLevelMap + $this->formatLevelMap;
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function log($level, $message, array $context = [])
    {
        $output = $this->output;
        $output->getFormatter()->setStyle('text', new OutputFormatterStyle('white'));
        $output->getFormatter()->setStyle('debug', new OutputFormatterStyle('blue', 'yellow'));

        if (!isset($this->verbosityLevelMap[$level])) {
            throw new InvalidArgumentException(\sprintf('The log level "%s" does not exist.', $level));
        }

        // default pattern: <level>message</level>
        $pattern = '<%1$s>%2$s%3$s</%1$s>';
        $prefix = '';

        // steps prefix
        if (isset($context['step'])) {
            $prefix = \sprintf('%s. ', $this->padPrefix($context['step'][0], $context['step'][1]));
        }

        // sub steps progress
        if (isset($context['progress'])) {
            // prefix
            $prefix = \sprintf(
                '[%s/%s] ',
                $this->padPrefix($context['progress'][0], $context['progress'][1]),
                $context['progress'][1]
            );
        }

        $output->writeln(
            \sprintf($pattern, $this->formatLevelMap[$level], $prefix, $this->interpolate($message, $context)),
            $this->verbosityLevelMap[$level]
        );
    }

    /**
     * Prefix padding.
     */
    private function padPrefix(string $prefix, string $max): string
    {
        return str_pad($prefix, strlen($max), ' ', STR_PAD_LEFT);
    }
}
