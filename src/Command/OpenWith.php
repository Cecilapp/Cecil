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
use Cecil\Util;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Open pages with an editor.
 */
class OpenWith extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('open')
            ->setDescription('Open pages directory with the editor')
            ->setDefinition([
                new InputArgument('path', InputArgument::OPTIONAL, 'Use the given path as working directory'),
                new InputOption('editor', null, InputOption::VALUE_REQUIRED, 'Editor to use'),
            ])
            ->setHelp(<<<'EOF'
The <info>%command.name%</> command open pages directory with the editor defined in the configuration file.

To open pages directory with the editor, run:

  <info>%command.full_name%</>

To open pages directory with the editor from a specific directory, run:

  <info>%command.full_name% path/to/directory</>

To open pages directory with a specific editor, run:

  <info>%command.full_name% --editor=editor</>
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
            if (null === $editor = $input->getOption('editor')) {
                if (!$this->getBuilder()->getConfig()->has('editor')) {
                    $output->writeln('<comment>No editor configured.</comment>');

                    return 0;
                }
                $editor = (string) $this->getBuilder()->getConfig()->get('editor');
            }
            $output->writeln(\sprintf('<info>Opening pages directory with %s...</info>', ucfirst($editor)));
            $this->openEditor(Util::joinFile($this->getPath(), (string) $this->getBuilder()->getConfig()->get('pages.dir')), $editor);
        } catch (\Exception $e) {
            throw new RuntimeException(\sprintf($e->getMessage()));
        }

        return 0;
    }
}
