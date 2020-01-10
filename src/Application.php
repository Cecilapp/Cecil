<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil;

use Symfony\Component\Console\Application as BaseApplication;

/**
 * The console application that handles the commands.
 */
class Application extends BaseApplication
{
    private static $banner = '  ____          _ _
 / ___|___  ___(_) | Your content driven
| |   / _ \/ __| | | static site generator.
| |__|  __/ (__| | |
 \____\___|\___|_|_| by Arnaud Ligny
';

    /**
     * {@inheritdoc}
     */
    public function getHelp()
    {
        return self::$banner.parent::getHelp();
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultCommands()
    {
        $commands = array_merge(parent::getDefaultCommands(), [
            new Command\CommandNewSite(),
            new Command\CommandBuild(),
            new Command\CommandClean(),
        ]);
        if (Util\Plateform::isPhar()) {
            $commands[] = new Command\CommandSelfUpdate();
        }

        return $commands;
    }
}
