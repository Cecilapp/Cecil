<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Command;

use Humbug\SelfUpdate\Updater;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CommandSelfUpdate extends Command
{
    /**
     * @var string
     */
    protected $version;
    /**
     * @var Updater
     */
    protected $updater;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('self-update')
            ->setAliases(['selfupdate'])
            ->setDescription('Update Cecil to the latest version')
            ->setDefinition(
                new InputDefinition([
                    new InputArgument('path', InputArgument::OPTIONAL, 'If specified, use the given path as working directory'),
                ])
            )
            ->setHelp('The self-update command checks for a newer version, and, if found, downloads and installs the latest.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->version = $this->getApplication()->getVersion();

        $this->updater = new Updater(null, false, Updater::STRATEGY_GITHUB);
        /* @var $strategy \Humbug\SelfUpdate\Strategy\GithubStrategy */
        $strategy = $this->updater->getStrategy();
        $strategy->setPackageName('cecil/cecil');
        $strategy->setPharName('cecil.phar');
        $strategy->setCurrentLocalVersion($this->version);
        $strategy->setStability('any');

        try {
            $output->writeln('Checks for updates...');
            $result = $this->updater->update();
            if ($result) {
                $new = $this->updater->getNewVersion();
                $old = $this->updater->getOldVersion();
                $output->writeln(sprintf('Updated from %s to %s.', $old, $new));

                return 0;
            }
            $output->writeln(sprintf('You are already using last version (%s).', $this->version));

            return 0;
        } catch (\Exception $e) {
            echo $e->getMessage();

            return 1;
        }

        return 0;
    }
}
