<?php
/**
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Shows the configuration.
 */
class ShowConfig extends Command
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
                    new InputArgument(
                        'path',
                        InputArgument::OPTIONAL,
                        'If specified, use the given path as working directory'
                    ),
                ])
            )
            ->setHelp('Shows the website\'s configuration');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Configuration:</info>');

        try {
            $output->writeln($this->printArray($this->getBuilder($output)->getConfig()->getAsArray()));
        } catch (\Exception $e) {
            throw new \Exception(sprintf($e->getMessage()));
        }

        return 0;
    }

    /**
     * Prints an array in console.
     *
     * @param array $array
     * @param int   $column
     *
     * @return string
     */
    private function printArray($array, $column = -2): string
    {
        $output = '';

        if (is_array($array)) {
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
        }

        return $output;
    }
}
