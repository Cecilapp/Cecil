<?php

declare(strict_types=1);

/*
 * This file is part of Cecil.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Command;

use Cecil\Exception\RuntimeException;
use Cecil\Util;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Creates a new website.
 */
class NewSite extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('new:site')
            ->setDescription('Creates a new website')
            ->setDefinition(
                new InputDefinition([
                    new InputArgument('path', InputArgument::OPTIONAL, 'Use the given path as working directory'),
                    new InputOption('force', 'f', InputOption::VALUE_NONE, 'Override directory if it already exists'),
                    new InputOption('demo', null, InputOption::VALUE_NONE, 'Add demo content (pages, templates and assets)'),
                ])
            )
            ->setHelp('Creates a new website in the current directory, or in <path> if provided');
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $force = $input->getOption('force');
        $demo = $input->getOption('demo');

        try {
            // ask to override existing site?
            if (Util\File::getFS()->exists(Util::joinFile($this->getPath(), $this->findConfigFile('name') ?: self::CONFIG_FILE[0])) && !$force) {
                $output->writeln('<comment>Website already exists.</comment>');
                if (!$this->io->confirm('Do you want to override it?', false)) {
                    return 0;
                }
            }
            // define root path
            $root = realpath(Util::joinFile(__DIR__, '/../../'));
            if (Util\Plateform::isPhar()) {
                $root = Util\Plateform::getPharPath() . '/';
            }
            // setup questions
            $title = $this->io->ask('Give a title to your new site', 'Site title');
            $baseline = $this->io->ask('Describe your site in few words', '');
            $baseurl = $this->io->ask('Base URL?', 'https://cecil.app/', [$this, 'validateUrl']);
            $description = $this->io->ask('Write a full description of your site', 'Site description.');
            $authorName = $this->io->ask('What is the author name?', 'Cecil');
            $authorUrl = $this->io->ask('What is the author URL?', 'https://cecil.app', [$this, 'validateUrl']);
            if ($this->io->confirm('Add demo content?', false)) {
                $demo = true;
            }
            // override skeleton default config
            $config = Yaml::parseFile(Util::joinPath($root, 'resources/skeleton', self::CONFIG_FILE[0]));
            $config = array_replace_recursive($config, [
                'title'       => $title,
                'baseline'    => $baseline,
                'baseurl'     => $baseurl,
                'description' => $description,
                'author'      => [
                    'name' => $authorName,
                    'url'  => $authorUrl,
                ],
            ]);
            $configYaml = Yaml::dump($config, 2, 2);
            Util\File::getFS()->dumpFile(Util::joinPath($this->getPath(), $this->findConfigFile('name') ?: self::CONFIG_FILE[0]), $configYaml);
            // creates dir
            foreach (
                [
                    (string) $this->getBuilder()->getConfig()->get('assets.dir'),
                    (string) $this->getBuilder()->getConfig()->get('layouts.dir'),
                    (string) $this->getBuilder()->getConfig()->get('pages.dir'),
                    (string) $this->getBuilder()->getConfig()->get('static.dir'),
                ] as $value
            ) {
                Util\File::getFS()->mkdir(Util::joinPath($this->getPath(), $value));
            }
            // copy files
            foreach (
                [
                    'assets/favicon.png',
                    'pages/index.md',
                    'static/cecil-card.png',
                ] as $value
            ) {
                Util\File::getFS()->copy(
                    Util::joinPath($root, 'resources/skeleton', $value),
                    Util::joinPath($this->getPath(), $value)
                );
            }
            // demo: copy all files
            if ($demo) {
                foreach (
                    [
                        (string) $this->getBuilder()->getConfig()->get('assets.dir'),
                        (string) $this->getBuilder()->getConfig()->get('layouts.dir'),
                        (string) $this->getBuilder()->getConfig()->get('pages.dir'),
                        (string) $this->getBuilder()->getConfig()->get('static.dir'),
                    ] as $value
                ) {
                    Util\File::getFS()->mirror(
                        Util::joinPath($root, 'resources/skeleton', $value),
                        Util::joinPath($this->getPath(), $value)
                    );
                }
            }
            // done
            $output->writeln(sprintf('<info>Your new Cecil site is created in %s.</info>', realpath($this->getPath())));
            $this->io->newLine();
            $this->io->listing([
                'You can download a theme from https://cecil.app/themes/',
                'You can create a new page with "cecil new:page"',
                'Start the built-in preview server via "cecil serve"',
            ]);
            $this->io->text('Visit https://cecil.app for full documentation.');
        } catch (\Exception $e) {
            throw new RuntimeException(sprintf($e->getMessage()));
        }

        return 0;
    }
}
