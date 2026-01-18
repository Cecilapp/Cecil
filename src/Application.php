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

use Cecil\DependencyInjection\ContainerBuilder;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The console application that handles the commands.
 *
 * This class extends the Symfony Console Application and integrates
 * the dependency injection container for service management.
 */
class Application extends BaseApplication
{
    /**
     * Banner of the application.
     * @var string
     */
    private static $banner = '  ____          _ _
 / ___|___  ___(_) |
| |   / _ \/ __| | | A simple and powerful content-driven static site generator.
| |__|  __/ (__| | |
 \____\___|\___|_|_| by Arnaud Ligny

';

    /**
     * Dependency injection container.
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->container = ContainerBuilder::build();
        parent::__construct('Cecil', Builder::VERSION);
    }

    /**
     * Gets the dependency injection container.
     *
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * {@inheritdoc}
     */
    public function getHelp(): string
    {
        return self::$banner . parent::getHelp();
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultCommands(): array
    {
        $commands = [
            new \Symfony\Component\Console\Command\HelpCommand(),
        ];

        // Liste des commandes à charger depuis le container
        $commandClasses = [
            'Cecil\\Command\\About',
            'Cecil\\Command\\NewSite',
            'Cecil\\Command\\NewPage',
            'Cecil\\Command\\Edit',
            'Cecil\\Command\\Build',
            'Cecil\\Command\\Serve',
            'Cecil\\Command\\Clear',
            'Cecil\\Command\\CacheClear',
            'Cecil\\Command\\CacheClearAssets',
            'Cecil\\Command\\CacheClearTemplates',
            'Cecil\\Command\\CacheClearTranslations',
            'Cecil\\Command\\ShowContent',
            'Cecil\\Command\\ShowConfig',
            'Cecil\\Command\\ListCommand',
            'Cecil\\Command\\UtilTranslationsExtract',
        ];

        foreach ($commandClasses as $commandClass) {
            try {
                // Instanciation directe car le container ne charge pas encore les services via resource
                $class = str_replace('\\\\', '\\', $commandClass);
                $command = new $class();
                if (method_exists($command, 'setContainer')) {
                    $command->setContainer($this->container);
                }
                $commands[] = $command;
            } catch (\Exception $e) {
                // Ignorer si la commande ne peut pas être instanciée
            }
        }

        if (Util\Platform::isPhar()) {
            try {
                $command = new \Cecil\Command\SelfUpdate();
                $command->setContainer($this->container);
                $commands[] = $command;
            } catch (\Exception $e) {
            }

            try {
                $command = new \Cecil\Command\UtilTemplatesExtract();
                $command->setContainer($this->container);
                $commands[] = $command;
            } catch (\Exception $e) {
            }
        }

        return $commands;
    }
}
