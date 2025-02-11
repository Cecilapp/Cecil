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
use Symfony\Component\Finder\Finder;

/**
 * Extracts built-in templates.
 */
class UtilTemplatesExtract extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('util:templates:extract')
            ->setDescription('Extracts built-in templates')
            ->setDefinition([
                new InputArgument('path', InputArgument::OPTIONAL, 'Use the given path as working directory'),
                new InputOption('force', 'f', InputOption::VALUE_NONE, 'Override files if they already exist'),
            ])
            ->setHelp(<<<'EOF'
The <info>%command.name%</> command extracts built-in templates in the "layouts" directory.

To extract built-in templates, run:

  <info>%command.full_name%</>

To extract built-in templates in a specific directory, run:

  <info>%command.full_name% path/to/directory</>

To override existing files, run:

  <info>%command.full_name% --force</>
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
        $force = $input->getOption('force');

        try {
            $phar = new \Phar(Util\Platform::getPharPath());

            $templatesList = [];
            $templates = Finder::create()
                ->files()
                ->in($this->getBuilder()->getConfig()->getLayoutsInternalPath());
            foreach ($templates as $template) {
                $templatesList[] = Util::joinPath((string) $this->getBuilder()->getConfig()->get('layouts.internal.dir'), Util\File::getFS()->makePathRelative($template->getPathname(), $this->getBuilder()->getConfig()->getLayoutsInternalPath()));
            }

            $force = ($force !== false) ?: $this->io->confirm('Do you want to override existing files?', false);

            $phar->extractTo($this->getBuilder()->getConfig()->getLayoutsPath(), $templatesList, $force);
            Util\File::getFS()->mirror(Util::joinPath($this->getBuilder()->getConfig()->getLayoutsPath(), (string) $this->getBuilder()->getConfig()->get('layouts.internal.dir')), $this->getBuilder()->getConfig()->getLayoutsPath());
            Util\File::getFS()->remove(Util::joinPath($this->getBuilder()->getConfig()->getLayoutsPath(), 'resources'));
            $output->writeln(\sprintf('<info>Built-in templates extracted to "%s".</info>', (string) $this->getBuilder()->getConfig()->get('layouts.dir')));
        } catch (\Exception $e) {
            throw new RuntimeException(\sprintf($e->getMessage()));
        }

        return 0;
    }
}
