<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ShowConfig extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('show:config')
            ->setDescription('Show configuration')
            ->setDefinition(
                new InputDefinition([
                    new InputArgument('path', InputArgument::OPTIONAL, 'If specified, use the given path as working directory'),
                ])
            )
            ->setHelp('Show Website configuration.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $output->writeln($this->printArray($this->getBuilder($output)->getConfig()->getAsArray()));
        } catch (\Exception $e) {
            throw new \Exception(sprintf($e->getMessage()));
        }

        return 0;
    }

    /**
     * Print an array in console.
     *
     * @param array $array
     * @param int $column
     */
    private function printArray($array, $column = -2)
    {
        $output = '';

        if (is_array($array)) {
            $column += 2;
            foreach ($array as $key => $val) {
                if (is_array($val)) {
                    $output .= str_repeat(' ', $column) . "$key:\n" . $this->printArray($val, $column);
                }
                if (is_string($val) || is_int($val)) {
                    $output .= str_repeat(' ', $column) . "$key: $val\n";
                }
                if (is_bool($val)) {
                    $output .= str_repeat(' ', $column) . "$key: " . ($val ? 'true' : 'false') . "\n";
                }
            }
        }

        return $output;
    }
}
