<?php

/*
 * This file is part of Cecil.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Command;

use Cecil\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Shows the configuration.
 */
class ShowConfig extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('show:config')
            ->setDescription('Shows the configuration')
            ->setDefinition(
                new InputDefinition([
                    new InputArgument('path', InputArgument::OPTIONAL, 'Use the given path as working directory'),
                    new InputOption('config', 'c', InputOption::VALUE_REQUIRED, 'Set the path to the config file'),
                ])
            )
            ->setHelp('Shows the website\'s configuration in YAML format');
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Configuration:</info>');

        try {
            $output->writeln($this->printArray($this->getBuilder()->getConfig()->getAsArray()));
        } catch (\Exception $e) {
            throw new RuntimeException(\sprintf($e->getMessage()));
        }

        return 0;
    }

    /**
     * Prints an array in console.
     */
    private function printArray(array $array, int $column = -2): string
    {
        $output = '';

        $column += 2;
        foreach ($array as $key => $val) {
            switch (gettype($val)) {
                case 'array':
                    $output .= str_repeat(' ', $column)."$key:\n".$this->printArray($val, $column);
                    break;
                case 'boolean':
                    $output .= str_repeat(' ', $column)."$key: ".($val ? 'true' : 'false')."\n";
                    break;
                default:
                    $output .= str_repeat(' ', $column)."$key: $val\n";
            }
        }

        return $output;
    }
}
