<?php

namespace Cecil\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Clean extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('clean')
            ->setDescription('Remove the output directory')
            ->setDefinition(
                new InputDefinition([
                    new InputArgument('path', InputArgument::OPTIONAL, 'Use the given path as working directory'),
                ])
            )
            ->setHelp('Remove the output directory and temporary files.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $outputDir = $this->getBuilder($output)->getConfig()->get('output.dir');
        if ($this->fs->exists($this->getPath().'/'.Serve::$tmpDir.'/output')) {
            $outputDir = file_get_contents($this->getPath().'/'.Serve::$tmpDir.'/output');
        }
        // delete output dir
        if ($this->fs->exists($this->getPath().'/'.$outputDir)) {
            $this->fs->remove($this->getPath().'/'.$outputDir);
            $output->writeln(sprintf("Output directory '%s' removed.", $outputDir));
        }
        // delete local server temp files
        if ($this->fs->exists($this->getPath().'/'.Serve::$tmpDir)) {
            $this->fs->remove($this->getPath().'/'.Serve::$tmpDir);
            $output->writeln('Temporary files deleted.');
        }

        return 0;
    }
}
