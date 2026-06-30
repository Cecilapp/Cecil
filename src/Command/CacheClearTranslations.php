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

use Cecil\Util;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CacheClearTranslations command.
 *
 * This command removes cached translations files from the translations cache directory.
 * It is useful for clearing outdated or unnecessary translations that may have been cached during previous builds.
 */
class CacheClearTranslations extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('cache:clear:translations')
            ->setDescription('Removes translations cache')
            ->setDefinition([
                new InputArgument('path', InputArgument::OPTIONAL, 'Use the given path as working directory'),
            ])
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</> command removes cached translations files.
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title('Removing translations cache directory');
        if (!Util\File::getFS()->exists($this->getBuilder()->getConfig()->getCacheTranslationsPath())) {
            $this->io->success('No translations cache.');

            return Command::SUCCESS;
        }
        $output->writeln(
            \sprintf('<comment>Path: %s</comment>', $this->getBuilder()->getConfig()->getCacheTranslationsPath()),
            OutputInterface::VERBOSITY_VERBOSE
        );
        Util\File::getFS()->remove($this->getBuilder()->getConfig()->getCacheTranslationsPath());
        $this->io->success('Translations cache cleared.');

        return Command::SUCCESS;
    }
}
