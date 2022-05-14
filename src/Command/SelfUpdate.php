<?php declare(strict_types=1);

/*
 * This file is part of Cecil.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Command;

use Humbug\SelfUpdate\Updater;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Updates Cecil to the latest version.
 */
class SelfUpdate extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('self-update')
            ->setDescription('Updates Cecil to the latest version')
            ->setDefinition(new InputDefinition([
                new InputOption('rollback', null, InputOption::VALUE_NONE, 'Revert to an older installation'),
                new InputOption('stable', null, InputOption::VALUE_NONE, 'Force an update to the last stable version'),
                new InputOption('preview', null, InputOption::VALUE_NONE, 'Force an update to the last unstable version'),
            ]))
            ->setHelp('The self-update command checks for a newer version and, if found, downloads and installs the latest');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $version = $this->getApplication()->getVersion();

        $updater = new Updater(null, false, Updater::STRATEGY_GITHUB);

        // rollback
        if ($input->getOption('rollback')) {
            try {
                $result = $updater->rollback();
                if (!$result) {
                    $output->writeln('Rollback failed.');

                    return 1;
                }
                $output->writeln('Rollback done.');

                return 0;
            } catch (\Exception $e) {
                $output->writeln($e->getMessage());

                return 1;
            }
        }

        /** @var \Humbug\SelfUpdate\Strategy\GithubStrategy $strategy */
        $strategy = $updater->getStrategy();
        $strategy->setPackageName('cecil/cecil');
        $strategy->setPharName('cecil.phar');
        $strategy->setCurrentLocalVersion($version);
        $strategy->setStability($input->getOption('preview') ? 'unstable' : 'stable');
        $updater->setStrategyObject($strategy);

        try {
            $output->writeln('Checking for updates...');
            $result = $updater->update();
            if ($result) {
                $new = $updater->getNewVersion();
                $old = $updater->getOldVersion();
                $output->writeln(\sprintf('Updated from <comment>%s</comment> to <info>%s</info>.', $old, $new));

                return 0;
            }
            $output->writeln(\sprintf('You are already using the last version (<comment>%s</comment>).', $version));

            return 0;
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());

            return 1;
        }
    }
}
