<?php

namespace Cecil\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CommandTest extends Command
{
    protected function configure()
    {
        $this
            ->setName('test')
            ->setDescription('This is a test.')
            ->setHelp('This command allows you to do a test...');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('A test! '.$input->getArgument('path'));

        return 0;
    }
}
