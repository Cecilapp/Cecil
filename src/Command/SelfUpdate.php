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

use Humbug\SelfUpdate\Updater;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * SelfUpdate command.
 *
 * This command checks for a newer version of Cecil and, if found, downloads and installs the latest version.
 * It can also revert to a previous version or force an update to the last stable or unstable version.
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
            ->setAliases(['selfupdate'])
            ->setDescription('Updates Cecil to the latest version')
            ->setDefinition([
                new InputOption('rollback', null, InputOption::VALUE_NONE, 'Revert to an older installation'),
                new InputOption('stable', null, InputOption::VALUE_NONE, 'Force an update to the last stable version'),
                new InputOption('preview', null, InputOption::VALUE_NONE, 'Force an update to the last unstable version'),
            ])
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</> command checks for a newer version and, if found, downloads and installs the latest.

  <info>%command.full_name%</>

To rollback to the <comment>previous</comment> version, run:

  <info>%command.full_name% --rollback</>

To update Cecil to the last <comment>stable</comment> version, run:

  <info>%command.full_name% --stable</>

To update Cecil to the last <comment>unstable</comment> version, run:

  <info>%command.full_name% --preview</>
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $version = $this->getApplication()->getVersion();

        $updater = new Updater(null, false, Updater::STRATEGY_GITHUB);

        // rollback
        if ($input->getOption('rollback')) {
            try {
                $result = $updater->rollback();
                if (!$result) {
                    $output->writeln('Rollback failed.');

                    return Command::FAILURE;
                }
                $output->writeln('Rollback done.');

                return Command::SUCCESS;;
            } catch (\Exception $e) {
                $output->writeln($e->getMessage());

                return Command::FAILURE;
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

                return Command::SUCCESS;;
            }
            $output->writeln(\sprintf('You are already using the last version (<comment>%s</comment>).', $version));

            return Command::SUCCESS;;
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());

            return Command::FAILURE;;
        }
    }
}
