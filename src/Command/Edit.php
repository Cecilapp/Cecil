<?php

/**
 * This file is part of Cecil.
 *
 * (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cecil\Command;

use Cecil\Exception\RuntimeException;
use Cecil\Util;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Edit command.
 *
 * This command opens the pages directory with the configured editor.
 * It allows users to quickly access and edit their content files directly from the command line.
 */
class Edit extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('edit')
            ->setAliases(['open'])
            ->setDescription('Open pages directory with the editor')
            ->setDefinition([
                new InputArgument('path', InputArgument::OPTIONAL, 'Use the given path as working directory'),
                new InputOption('editor', null, InputOption::VALUE_REQUIRED, 'Editor to use'),
            ])
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</> command open pages directory with the <comment>editor defined</comment> in the configuration file.

  <info>%command.full_name%</>
  <info>%command.full_name% path/to/the/working/directory</>

To open pages directory with a <comment>specific editor</comment>, run:

  <info>%command.full_name% --editor=editor</>
EOF
            );
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
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
