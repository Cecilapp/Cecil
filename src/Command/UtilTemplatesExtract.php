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
use Symfony\Component\Finder\Finder;

/**
 * UtilTemplatesExtract command.
 *
 * This command extracts built-in templates from the Phar archive to the specified layouts directory.
 * It can override existing files if the --force option is provided.
 * If no path is provided, it uses the default layouts directory defined in the configuration.
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
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</> command extracts built-in templates in the "layouts" directory.

  <info>%command.full_name%</>
  <info>%command.full_name% path/to/the/working/directory</>

To <comment>override</comment> existing files, run:

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
                $relativePath = rtrim(Util\File::getFS()->makePathRelative($template->getPathname(), $this->getBuilder()->getConfig()->getLayoutsInternalPath()), '/');
                if (!isset($phar["resources/layouts/$relativePath"])) {
                    throw new RuntimeException(\sprintf('Internal template `%s` doesn\'t exist.', $relativePath));
                }
                $templatesList[] = "resources/layouts/$relativePath";
            }

            $force = ($force !== false) ?: $this->io->confirm('Do you want to override existing files?', false);

            $phar->extractTo($this->getBuilder()->getConfig()->getLayoutsPath(), $templatesList, $force);
            Util\File::getFS()->mirror(Util::joinPath($this->getBuilder()->getConfig()->getLayoutsPath(), 'resources/layouts/'), $this->getBuilder()->getConfig()->getLayoutsPath());
            Util\File::getFS()->remove(Util::joinPath($this->getBuilder()->getConfig()->getLayoutsPath(), 'resources'));
            $output->writeln(\sprintf('<info>Built-in templates extracted to "%s".</info>', (string) $this->getBuilder()->getConfig()->get('layouts.dir')));
        } catch (\Exception $e) {
            throw new RuntimeException($e->getMessage());
        }

        return 0;
    }
}
