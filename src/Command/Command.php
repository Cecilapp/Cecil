<?php
/**
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Command;

use Cecil\Builder;
use Cecil\Util;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class Command extends BaseCommand
{
    const CONFIG_FILE = 'config.yml';
    const TMP_DIR = '.cecil';

    /** @var InputInterface */
    protected $input;
    /** @var OutputInterface */
    protected $output;
    /** @var SymfonyStyle */
    protected $io;
    /** @var Filesystem */
    protected $fs;
    /** @var string */
    protected $path;
    /** @var string */
    protected $configFile;
    /** @var Builder */
    protected $builder;
    /** @var ProgressBar */
    protected $progressBar = null;
    /** @var int */
    protected $progressBarMax;

    /**
     * {@inheritdoc}
     */
    public function run(InputInterface $input, OutputInterface $output)
    {
        if ($output->isDebug()) {
            parent::run($input, $output);
        }
        // simplifying error message
        try {
            parent::run($input, $output);
        } catch (\Exception $e) {
            $this->io->error($e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->io = new SymfonyStyle($input, $output);
        $this->fs = new Filesystem();

        if (!in_array($this->getName(), ['self-update'])) {
            $this->path = (string) $input->getArgument('path');
            if (null === $this->getPath()) {
                $this->path = getcwd();
            }
            if (false === realpath($this->getPath())) {
                if (!in_array($this->getName(), ['new:site'])) {
                    throw new \InvalidArgumentException(sprintf('"%s" is not valid path.', $this->getPath()));
                }
                $output->writeln(sprintf(
                    '<comment>The provided <path> "%s" doesn\'t exist.</comment>',
                    $this->getpath()
                ));
                // ask to create path
                $helper = $this->getHelper('question');
                $question = new ConfirmationQuestion('Do you want to create it? [y/n]', false);
                if (!$helper->ask($input, $output, $question)) {
                    exit(0);
                }

                $this->fs->mkdir($this->getPath());
            }
            $this->path = realpath($this->getPath());
            $this->configFile = Util::joinFile($this->getPath(), self::CONFIG_FILE);

            if (!in_array($this->getName(), ['new:site'])) {
                if (!file_exists($this->configFile)) {
                    $message = sprintf('Cecil could not find "%s" file in "%s"', self::CONFIG_FILE, $this->getPath());

                    throw new \InvalidArgumentException($message);
                }
            }
        }

        parent::initialize($input, $output);
    }

    /**
     * Returns the working directory.
     *
     * @return string|null
     */
    protected function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * Creates or returns a Builder instance.
     *
     * @param array $config
     *
     * @return Builder
     */
    protected function getBuilder(array $config = ['debug' => false]): Builder {
        if (!file_exists($this->configFile)) {
            throw new \Exception(sprintf('Config file not found in "%s"!', $this->getPath()));
        }

        try {
            $siteConfig = Yaml::parse(file_get_contents($this->configFile));
            $config = array_replace_recursive($siteConfig, $config);
            $this->builder = (new Builder($config, $this->messageCallback($this->output)))
                ->setSourceDir($this->getPath())
                ->setDestinationDir($this->getPath());
        } catch (ParseException $e) {
            throw new \Exception(sprintf('Config file parse error: %s', $e->getMessage()));
        } catch (\Exception $e) {
            throw new \Exception(sprintf($e->getMessage()));
        }

        return $this->builder;
    }

    /**
     * Creates the Progress bar.
     *
     * @param int $max
     *
     * @return void
     */
    protected function createProgressBar($max): void
    {
        if ($this->progressBar === null || $max != $this->progressBarMax) {
            $this->progressBarMax = $max;
            $this->progressBar = new ProgressBar($this->output, $max);
            $this->progressBar->setOverwrite(true);
            $this->progressBar->setFormat(' %percent:3s%% [%bar%] %current%/%max%');
            $this->progressBar->setBarCharacter('#');
            $this->progressBar->setEmptyBarCharacter(' ');
            $this->progressBar->setProgressCharacter('#');
            $this->progressBar->setRedrawFrequency(1);
            $this->progressBar->start();
        }
    }

    /**
     * Returns the Progress Bar.
     *
     * @return ProgressBar
     */
    protected function getProgressBar(): ProgressBar
    {
        return $this->progressBar;
    }

    /**
     * Print the Progress Bar.
     *
     * @param int $itemsCount
     * @param int $itemsMax
     *
     * @return void
     */
    protected function printProgressBar($itemsCount, $itemsMax): void
    {
        $this->createProgressBar($itemsMax);
        $this->getProgressBar()->clear();
        $this->getProgressBar()->setProgress($itemsCount);
        $this->getProgressBar()->display();
        if ($itemsCount == $itemsMax) {
            $this->getProgressBar()->finish();
            $this->output->writeln('');
        }
    }

    /**
     * Customs messages callback function.
     *
     * @return \Closure
     */
    public function messageCallback(): \Closure
    {
        return function ($code, $message = '', $itemsCount = 0, $itemsMax = 0) {
            if (strpos($code, '_PROGRESS') !== false) {
                if ($this->output->isVerbose()) {
                    if ($itemsCount > 0) {
                        $this->output->writeln(sprintf(' (%u/%u) %s', $itemsCount, $itemsMax, $message));

                        return;
                    }
                    $this->output->writeln(" $message");

                    return;
                }
                if (isset($itemsCount) && $itemsMax > 0) {
                    $this->printProgressBar($itemsCount, $itemsMax);

                    return;
                }
                $this->output->writeln(" $message");

                return;
            } elseif (strpos($code, '_ERROR') !== false) {
                $this->output->writeln("<error>$message</error>");

                return;
            } elseif ($code == 'TIME') {
                $this->output->writeln("<comment>$message</comment>");

                return;
            }
            $this->output->writeln("<info>$message</info>");
        };
    }
}
