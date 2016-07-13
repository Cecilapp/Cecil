<?php
/*
 * This file is part of the PHPoole package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Command;

use Humbug\SelfUpdate\Updater;

class SelfUpdate extends AbstractCommand
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
     * @param mixed $version
     */
    public function __construct($version)
    {
        $this->version = $version;

        $this->updater = new Updater(null, false, Updater::STRATEGY_GITHUB);
        /* @var $strategy \Humbug\SelfUpdate\Strategy\GithubStrategy */
        $strategy = $this->updater->getStrategy();
        $strategy->setPackageName('narno/phpoole');
        $strategy->setPharName('phpoole.phar');
        $strategy->setCurrentLocalVersion($this->version);
        $strategy->setStability('any');
    }

    public function processCommand()
    {
        try {
            echo "\rChecks for updates...";
            $result = $this->updater->update();
            if ($result) {
                $new = $this->updater->getNewVersion();
                $old = $this->updater->getOldVersion();
                printf("\rUpdated from %s to %s.", $old, $new);
                exit(0);
            }
            printf("\rYou are already using last version (%s).", $this->version);
            exit(0);
        } catch (\Exception $e) {
            echo $e->getMessage();
            exit(1);
        }
    }
}
