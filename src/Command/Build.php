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

use Cecil\Util;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Builds the website.
 */
class Build extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('build')
            ->setDescription('Builds the website')
            ->setDefinition(
                new InputDefinition([
                    new InputArgument('path', InputArgument::OPTIONAL, 'Use the given path as working directory'),
                    new InputOption('config', 'c', InputOption::VALUE_REQUIRED, 'Set the path to the config file'),
                    new InputOption('drafts', 'd', InputOption::VALUE_NONE, 'Include drafts'),
                    new InputOption('dry-run', null, InputOption::VALUE_NONE, 'Build without saving'),
                    new InputOption('baseurl', null, InputOption::VALUE_REQUIRED, 'Set the base URL'),
                    new InputOption('output', null, InputOption::VALUE_REQUIRED, 'Set the output directory'),
                    new InputOption(
                        'postprocess',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'Post-process output (disable with "no")',
                        false
                    ),
                    new InputOption('clear-cache', null, InputOption::VALUE_NONE, 'Clear cache after build'),
                ])
            )
            ->setHelp('Builds the website in the output directory');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = [];
        $options = [];
        $messageOpt = '';

        if ($input->getOption('baseurl')) {
            $config['baseurl'] = $input->getOption('baseurl');
        }
        if ($input->getOption('output')) {
            $config['output']['dir'] = $input->getOption('output');
            $this->fs->dumpFile(
                Util::joinFile($this->getPath(), self::TMP_DIR, 'output'),
                (string) $input->getOption('output')
            );
        }
        if ($input->getOption('postprocess') === null) {
            $config['postprocess']['enabled'] = true;
        }
        if ($input->getOption('postprocess') == 'no') {
            $config['postprocess']['enabled'] = false;
        }
        if ($input->getOption('clear-cache')) {
            $config['cache']['enabled'] = false;
        }
        if ($input->getOption('drafts')) {
            $options['drafts'] = true;
            $messageOpt .= ' with drafts';
        }
        if ($input->getOption('dry-run')) {
            $options['dry-run'] = true;
            $messageOpt .= ' (dry-run)';
        }

        $output->writeln(sprintf('Building website%s...', $messageOpt));
        $output->writeln(
            sprintf('<comment>Path: %s</comment>', $this->getPath()),
            OutputInterface::VERBOSITY_VERBOSE
        );
        if ($this->getConfigFile() !== null) {
            $output->writeln(
                sprintf('<comment>Config: %s</comment>', $this->getConfigFile()),
                OutputInterface::VERBOSITY_VERBOSE
            );
        }
        if ((bool) $this->builder->getConfig()->get('cache.enabled')) {
            $output->writeln(
                sprintf('<comment>Cache: %s</comment>', $this->builder->getConfig()->getCachePath()),
                OutputInterface::VERBOSITY_VERBOSE
            );
        }

        $builder = $this->getBuilder($config);
        $builder->build($options);
        $this->fs->dumpFile(Util::joinFile($this->getPath(), self::TMP_DIR, 'changes.flag'), time());
        $output->writeln('Done! 🎉');

        return 0;
    }
}
