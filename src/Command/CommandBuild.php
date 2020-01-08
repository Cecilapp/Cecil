<?php

namespace Cecil\Command;

use Symfony\Component\Console\Input\InputArgument;
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
