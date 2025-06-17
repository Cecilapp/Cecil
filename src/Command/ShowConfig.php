<?php

declare(strict_types=1);

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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * ShowConfig command.
 *
 * This command displays the website's configuration in YAML format.
 * It can be used to quickly review the current configuration settings.
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
            ->setDefinition([
                new InputArgument('path', InputArgument::OPTIONAL, 'Use the given path as working directory'),
                new InputOption('config', 'c', InputOption::VALUE_REQUIRED, 'Set the path to an extra configuration file'),
            ])
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</> command shows the website\'s configuration in YAML format.

  <info>%command.full_name%</>
  <info>%command.full_name% path/to/the/working/directory</>

To show the configuration with an extra configuration file, run:

  <info>%command.full_name% --config=config.yml</>
EOF
            );
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $output->writeln($this->arrayToYaml($this->getBuilder()->getConfig()->export()));
        } catch (\Exception $e) {
            throw new RuntimeException($e->getMessage());
        }

        return 0;
    }

    /**
     * Converts an array to YAML.
     */
    private function arrayToYaml(array $array): string
    {
        return trim(Yaml::dump($array, 6, 2));
    }
}
