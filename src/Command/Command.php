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
                $this->fs->mkdir($this->getPath());
            }
            $this->path = realpath($this->getPath());
            $this->configFile = Util::joinFile($this->getPath(), self::CONFIG_FILE);

            if (!in_array($this->getName(), ['new:site'])) {
                if (!file_exists($this->configFile)) {
                    $message = sprintf('Could not find "%s" file in "%s"', self::CONFIG_FILE, $this->getPath());

                    throw new \InvalidArgumentException($message);
                }
            }
        }

        parent::initialize($input, $output);
    }

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
    protected function getBuilder(array $config = []): Builder
    {
        if (!file_exists($this->configFile)) {
            throw new \Exception(sprintf('Configuration file not found in "%s"!', $this->getPath()));
        }

        try {
            $siteConfig = Yaml::parse(Util::fileGetContents($this->configFile));
            if ($siteConfig === false) {
                throw new \Exception('Can\'t read the configuration file.');
            }
            $config = array_replace_recursive($siteConfig, $config);
            $this->builder = (new Builder($config, $this->messageCallback()))
                ->setSourceDir($this->getPath())
                ->setDestinationDir($this->getPath());
        } catch (ParseException $e) {
            throw new \Exception(sprintf('Configuration file parse error: %s', $e->getMessage()));
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
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
    protected function createProgressBar(int $max): void
    {
        if ($this->progressBar === null || $max != $this->progressBarMax) {
            $this->progressBarMax = $max;
            $this->progressBar = new ProgressBar($this->output, $max);
            $this->progressBar->setOverwrite(true);
            $this->progressBar->setFormat(' %percent:3s%% [%bar%] %current%/%max% %message%');
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
     * Prints the Progress Bar.
     *
     * @param int    $itemsCount
     * @param int    $itemsMax
     * @param string $message
     *
     * @return void
     */
    protected function printProgressBar(int $itemsCount, int $itemsMax, string $message = ''): void
    {
        $this->createProgressBar($itemsMax);
        $this->getProgressBar()->clear();
        $this->getProgressBar()->setProgress($itemsCount);
        $this->getProgressBar()->setMessage($message);
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
            $output = $this->output;
            if (strpos($code, '_PROGRESS') !== false) {
                if ($output->isVerbose()) {
                    if ($itemsCount > 0) {
                        $output->writeln(sprintf(' (%u/%u) %s', $itemsCount, $itemsMax, $message));

                        return;
                    }
                    $output->writeln(" $message");

                    return;
                }
                if (isset($itemsCount) && $itemsMax > 0) {
                    $this->printProgressBar($itemsCount, $itemsMax);

                    return;
                }
                $output->writeln(" $message");

                return;
            } elseif (strpos($code, '_ERROR') !== false) {
                $output->writeln(" <error>$message</error>");

                return;
            } elseif ($code == 'TIME') {
                $output->writeln("<comment>$message</comment>");

                return;
            }
            $output->writeln("<info>$message</info>");
        };
    }
}
