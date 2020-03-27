<?php
/*
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
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
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class Command extends BaseCommand
{
    const CONFIG_FILE = 'config.yml';
    const TMP_DIR = '.cecil';

    /**
     * @var Filesystem
     */
    protected $fs;
    /**
     * @var string
     */
    protected $path;
    /**
     * @var string
     */
    protected $configFile;
    /**
     * @var Builder
     */
    protected $builder;
    /**
     * @var ProgressBar
     */
    protected $progressBar = null;
    /**
     * @var int
     */
    protected $progressBarMax;

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->fs = new Filesystem();

        if (!in_array($this->getName(), ['self-update'])) {
            $this->path = (string) $input->getArgument('path');
            if (null === $this->getPath()) {
                $this->path = getcwd();
            }
            if (false === realpath($this->getPath())) {
                if (!in_array($this->getName(), ['new:site'])) {
                    $message = sprintf('"%s" is not valid path.', $this->getPath());

                    throw new \InvalidArgumentException($message);
                }
                $helper = $this->getHelper('question');
                $question = new ConfirmationQuestion(
                    sprintf('The provided <path> "%s" doesn\'t exist.
Do you want to create it? [y/n]', $this->getpath()),
                    false
                );
                if (!$helper->ask($input, $output, $question)) {
                    return;
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
     * Return the working directory.
     *
     * @return string|null
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * Create or return a Builder instance.
     *
     * @param OutputInterface $output
     * @param array           $config
     *
     * @return Builder
     */
    public function getBuilder(
        OutputInterface $output,
        array $config = ['debug' => false]
    ): Builder {
        if (!file_exists($this->configFile)) {
            throw new \Exception(sprintf('Config file not found in "%s"!', $this->getPath()));
        }

        try {
            $siteConfig = Yaml::parse(file_get_contents($this->configFile));
            $config = array_replace_recursive($siteConfig, $config);
            $this->builder = (new Builder($config, $this->messageCallback($output)))
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
     * Create the Progress bar.
     *
     * @param OutputInterface $output
     * @param int             $max
     */
    protected function createProgressBar(OutputInterface $output, $max)
    {
        if ($this->progressBar === null || $max != $this->progressBarMax) {
            $this->progressBarMax = $max;
            $this->progressBar = new ProgressBar($output, $max);
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
     * Return Progress Bar.
     *
     * @return ProgressBar
     */
    protected function getProgressBar()
    {
        return $this->progressBar;
    }

    /**
     * Print the Progress Bar.
     *
     * @param OutputInterface $output
     * @param int             $itemsCount
     * @param int             $itemsMax
     */
    protected function printProgressBar(OutputInterface $output, $itemsCount, $itemsMax)
    {
        $this->createProgressBar($output, $itemsMax);
        $this->getProgressBar()->clear();
        $this->getProgressBar()->setProgress($itemsCount);
        $this->getProgressBar()->display();
        if ($itemsCount == $itemsMax) {
            $this->getProgressBar()->finish();
            $output->writeln('');
        }
    }

    /**
     * Custom message callback function.
     *
     * @param OutputInterface $output
     */
    public function messageCallback(OutputInterface $output)
    {
        return function ($code, $message = '', $itemsCount = 0, $itemsMax = 0) use ($output) {
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
                    $this->printProgressBar($output, $itemsCount, $itemsMax);

                    return;
                }
                $output->writeln(" $message");

                return;
            } elseif (strpos($code, '_ERROR') !== false) {
                $output->writeln("<error>$message</error>");

                return;
            } elseif ($code == 'TIME') {
                $output->writeln("<comment>$message</comment>");

                return;
            }
            $output->writeln("<info>$message</info>");
        };
    }
}
