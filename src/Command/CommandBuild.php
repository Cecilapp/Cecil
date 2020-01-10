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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CommandBuild extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('build')
            ->setAliases(['b'])
            ->setDescription('Build the website')
            ->setDefinition(
                new InputDefinition([
                    new InputArgument('path', InputArgument::OPTIONAL, 'If specified, use the given path as working directory'),
                    new InputOption('drafts', 'd', InputOption::VALUE_NONE, 'Include drafts'),
                    new InputOption('dry-run', null, InputOption::VALUE_NONE, 'Build without saving'),
                    new InputOption('baseurl', null, InputOption::VALUE_REQUIRED, 'Set the base URL'),
                    new InputOption('destination', null, InputOption::VALUE_REQUIRED, 'Set the output directory'),
                ])
            )
            ->setHelp('Build the website in the output directory.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = [];
        $options = [];
        $messageOpt = '';

        if ($input->getOption('drafts')) {
            $options['drafts'] = true;
            $messageOpt .= ' with drafts';
        }
        if ($input->getOption('dry-run')) {
            $options['dry-run'] = true;
            $messageOpt .= ' dry-run';
        }
        if ($input->getOption('baseurl')) {
            $config['baseurl'] = $input->getOption('baseurl');
        }
        if ($input->getOption('destination')) {
            $config['output']['dir'] = $input->getOption('destination');
            $this->fs->dumpFile($this->getPath().'/'.Serve::$tmpDir.'/output', $input->getOption('destination'));
        }

        try {
            $output->writeln(sprintf('Building website%s...', $messageOpt));
            $output->writeln(sprintf('<comment>Path: %s</comment>', $this->getPath()));
            $this->getBuilder($output, $config)->build($options);
            //if ($this->getRoute()->getName() == 'serve') {
            $this->fs->dumpFile($this->getPath().'/'.Serve::$tmpDir.'/changes.flag', '');
            //}
        } catch (\Exception $e) {
            throw new \Exception(sprintf('%s', $e->getMessage()));
        }

        return 0;
    }
}
