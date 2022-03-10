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

use Cecil\Exception\RuntimeException;
use Cecil\Util;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Open content with an editor.
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
            ->setDescription('Open content with the editor')
            ->setDefinition(
                new InputDefinition([
                    new InputArgument('path', InputArgument::OPTIONAL, 'Use the given path as working directory'),
                ])
            )
            ->setHelp('Open content directory with the editor defined in the configuration file.)');
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            if (!$this->hasEditor()) {
                $output->writeln('<comment>No editor configured.</comment>');

                return 0;
            }
            $output->writeln(\sprintf('<info>Opening content directory with %s...</info>', (string) $this->getBuilder()->getConfig()->get('editor')));
            $this->openEditor((string) Util::joinFile($this->getPath(), $this->getBuilder()->getConfig()->get('content.dir')));
        } catch (\Exception $e) {
            throw new RuntimeException(\sprintf($e->getMessage()));
        }

        return 0;
    }
}
