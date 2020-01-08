<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputArgument;

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

    public function getHelp()
    {
        return self::$banner.parent::getHelp();
    }

    /**
     * Initializes all the composer commands.
     */
    protected function getDefaultCommands()
    {
        $commands = array_merge(parent::getDefaultCommands(), [
            new Command\CommandTest(),
            new Command\CommandBuild(),
        ]);
        if (Util\Plateform::isPhar()) {
            $commands[] = new Command\SelfUpdate($this->getVersion());
        }

        return $commands;
    }

    protected function getDefaultInputDefinition()
    {
        $definition = parent::getDefaultInputDefinition();
        $definition->addArgument(new InputArgument('path', InputArgument::OPTIONAL, 'Path to the Website'));

        return $definition;
    }
}
