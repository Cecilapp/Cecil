<?php

namespace Cecil\Command;

use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CommandBuild extends Command
{
    protected function configure()
    {
        $this
            ->setName('build')
            ->setDescription('Build the website')
            ->setHelp('Build the website in the output directory.')
            ->setDefinition(
                new InputDefinition([
                    new InputOption('drafts', 'd', InputOption::VALUE_NONE, 'Include drafts'),
                    new InputOption('dry-run', null, InputOption::VALUE_NONE, 'Build without saving'),
                    new InputOption('baseurl', null, InputOption::VALUE_REQUIRED, 'Set the base URL'),
                    new InputOption('destination', null, InputOption::VALUE_REQUIRED, 'Set the output directory'),
                ])
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = [];
        $options = [];

        if ($input->getOption('drafts')) {
            $options['drafts'] = true;
            $messageOpt .= ' with drafts';
        }
        if ($input->getOption('dry-run')) {
            $options['dry-run'] = true;
            $messageOpt .= ' dry-run';
        }
        if ($input->getOption('baseurl')) {
            $config['site']['baseurl'] = $input->getOption('baseurl');
        }
        if ($input->getOption('destination')) {
            $config['site']['output']['dir'] = $input->getOption('destination');
            $this->fs->dumpFile($this->getPath().'/'.Serve::$tmpDir.'/output', $input->getOption('destination'));
        }

        try {
            if (!$this->quiet) {
                $output->writeln(sprintf('Building website%s...', $messageOpt));
                $output->writeln(sprintf('<comment>Path: %s</comment>', $this->getPath()));
            }
            $this->getBuilder($output, $config, $options)->build($options);
            //if ($this->getRoute()->getName() == 'serve') {
            //    $this->fs->dumpFile($this->getPath() . '/' . Serve::$tmpDir . '/changes.flag', '');
            //}
        } catch (\Exception $e) {
            throw new \Exception(sprintf('%s', $e->getMessage()));
        }

        return 0;
    }
}
