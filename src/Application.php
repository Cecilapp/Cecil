<?php declare(strict_types=1);

/*
 * This file is part of Cecil.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
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
 / ___|___  ___(_) |
| |   / _ \/ __| | | Your content driven static site generator.
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
            new Command\NewSite(),
            new Command\NewPage(),
            new Command\OpenWith(),
            new Command\Build(),
            new Command\Serve(),
            new Command\Clear(),
            new Command\CacheClear(),
            new Command\CacheClearAssets(),
            new Command\CacheClearTemplates(),
            new Command\ShowContent(),
            new Command\ShowConfig(),
            new Command\ListCommand(),
        ]);
        if (Util\Plateform::isPhar()) {
            $commands[] = new Command\SelfUpdate();
        }

        return $commands;
    }
}
