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

namespace Cecil;

use Symfony\Component\Console\Application as BaseApplication;

/**
 * The console application that handles the commands.
 *
 * This class extends the Symfony Console Application.
 */
class Application extends BaseApplication
{
    /**
     * Author of the application.
     * @var string
     */
    private static $author = 'Arnaud Ligny';

    /**
     * Description of the application.
     * @var string
     */
    private static $description = 'Cecil is a simple and powerful content-driven static site generator.';

    /**
     * {@inheritdoc}
     */
    public function getHelp(): string
    {
        $response = [
            \sprintf('<info>%s</info> version <comment>%s</comment> (c) 2013-%s %s', $this->getName(), $this->getVersion(), date('Y'), self::$author),
            self::$description,
        ];

        return implode("\n\n", $response);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultCommands(): array
    {
        $commands = [
            new \Symfony\Component\Console\Command\HelpCommand(),
            new Command\About(),
            new Command\NewSite(),
            new Command\NewPage(),
            new Command\Edit(),
            new Command\Build(),
            new Command\Serve(),
            new Command\Stop(),
            new Command\Clear(),
            new Command\ClearOutput(),
            new Command\ClearTmp(),
            new Command\CacheClear(),
            new Command\CacheClearAssets(),
            new Command\CacheClearTemplates(),
            new Command\CacheClearTranslations(),
            new Command\Doctor(),
            new Command\DoctorFrontmatter(),
            new Command\DoctorSeo(),
            new Command\ShowContent(),
            new Command\ShowConfig(),
            new Command\ListCommand(),
            new Command\UtilTranslationsExtract()
        ];
        if (Util\Platform::isPhar()) {
            $commands[] = new Command\SelfUpdate();
            $commands[] = new Command\UtilTemplatesExtract();
        }

        return $commands;
    }
}
