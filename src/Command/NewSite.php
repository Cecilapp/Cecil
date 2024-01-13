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
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
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

        try {
            // ask to override site?
            /** @var \Symfony\Component\Console\Helper\QuestionHelper $helper */
            $helper = $this->getHelper('question');
            if (Util\File::getFS()->exists(Util::joinFile($this->getPath(), $this->findConfigFile('name') ?: self::CONFIG_FILE[0])) && !$force) {
                $output->writeln('<comment>Website already exists.</comment>');
                if (!$helper->ask($input, $output, new ConfirmationQuestion('Do you want to override it? [y/n]', false))) {
                    return 0;
                }
            }
            // define root path
            $root = realpath(Util::joinFile(__DIR__, '/../../'));
            if (Util\Plateform::isPhar()) {
                $root = Util\Plateform::getPharPath() . '/';
            }
            // ask for basic configuration
            $output->writeln('Creating a new website...');
            $title = $helper->ask($input, $output, new Question('- title: ', 'Site title'));
            $baseline = $helper->ask($input, $output, new Question('- baseline (~ 20 characters): ', 'Site baseline'));
            $baseurl = $helper->ask($input, $output, new Question('- baseurl (e.g.: https://cecil.app/): ', 'http://localhost:8000/'));
            $description = $helper->ask($input, $output, new Question('- description (~ 250 characters): ', 'Site description.'));
            // override skeleton default config
            $config = Yaml::parseFile(Util::joinPath($root, 'resources/skeleton', self::CONFIG_FILE[0]));
            $config = array_replace_recursive($config, [
                'title'       => $title,
                'baseline'    => $baseline,
                'baseurl'     => $baseurl,
                'description' => $description,
            ]);
            $configYaml = Yaml::dump($config);
            Util\File::getFS()->dumpFile(Util::joinPath($this->getPath(), $this->findConfigFile('name') ?: self::CONFIG_FILE[0]), $configYaml);
            // files copy
            foreach (
                [
                    (string) $this->getBuilder()->getConfig()->get('pages.dir'),
                    (string) $this->getBuilder()->getConfig()->get('layouts.dir'),
                    (string) $this->getBuilder()->getConfig()->get('static.dir'),
                    (string) $this->getBuilder()->getConfig()->get('assets.dir'),
                ] as $value
            ) {
                Util\File::getFS()->mirror(
                    Util::joinPath($root, 'resources/skeleton', $value),
                    Util::joinPath($this->getPath(), $value)
                );
            }
            $output->writeln('<info>Done</info>');
        } catch (\Exception $e) {
            throw new RuntimeException(sprintf($e->getMessage()));
        }

        return 0;
    }
}
