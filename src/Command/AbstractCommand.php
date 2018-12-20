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
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Zend\Console\Adapter\AdapterInterface as Console;
use Zend\Console\ColorInterface as Color;
use Zend\Console\Prompt\Confirm;
use Zend\ProgressBar\ProgressBar;
use ZF\Console\Route;

/**
 * Abstract class AbstractCommand.
 */
abstract class AbstractCommand
{
    const CONFIG_FILE = 'config.yml';

    /**
     * @var Console
     */
    protected $console;
    /**
     * @var Route
     */
    protected $route;
    /**
     * @var string
     */
    protected $path;
    /**
     * @var Builder
     */
    protected $builder;
    /**
     * @var Filesystem
     */
    protected $fs;
    /**
     * @var ProgressBar
     */
    protected $progressBar = null;
    /**
     * @var int
     */
    protected $progressBarMax = 0;
    /**
     * @var bool
     */
    protected $verbose = false;
    /**
     * @var bool
     */
    protected $quiet = false;

    /**
     * Start command processing.
     *
     * @param Route   $route
     * @param Console $console
     *
     * @return mixed
     */
    public function __invoke(Route $route, Console $console)
    {
        $this->route = $route;
        $this->console = $console;
        $this->fs = new Filesystem();

        $this->path = $this->route->getMatchedParam('path', getcwd());

        if (realpath($this->path) === false) {
            if ($this->getRoute()->getName() != 'new') {
                throw new \Exception('Invalid <path> provided!');
            }
            if (!Confirm::prompt('The provided <path> doesn\'t exist. Do you want to create it? [y/n]', 'y', 'n')) {
                exit(0);
            }
            $this->fs->mkdir($this->path);
        }
        $this->path = realpath($this->path);
        $this->path = str_replace(DIRECTORY_SEPARATOR, '/', $this->path);

        return $this->processCommand();
    }

    /**
     * Process the command.
     */
    abstract public function processCommand();

    /**
     * @return Console
     */
    public function getConsole()
    {
        return $this->console;
    }

    /**
     * @return Route
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param int $start
     * @param int $max
     *
     * @return ProgressBar
     */
    protected function createProgressBar($start, $max)
    {
        if ($this->progressBar === null || $max != $this->progressBarMax) {
            $this->progressBarMax = $max;
            $adapter = new \Zend\ProgressBar\Adapter\Console([
                'elements' => [
                    \Zend\ProgressBar\Adapter\Console::ELEMENT_PERCENT,
                    \Zend\ProgressBar\Adapter\Console::ELEMENT_BAR,
                    \Zend\ProgressBar\Adapter\Console::ELEMENT_TEXT,
                ],
                'textWidth' => 30,
            ]);
            $this->progressBar = new ProgressBar($adapter, $start, $max);
        }

        return $this->progressBar;
    }

    /**
     * @return ProgressBar
     */
    protected function getProgressBar()
    {
        return $this->progressBar;
    }

    /**
     * Print progress bar.
     *
     * @param int    $itemsCount
     * @param int    $itemsMax
     * @param string $message
     */
    protected function printProgressBar($itemsCount, $itemsMax, $message)
    {
        $this->createProgressBar(0, $itemsMax);
        $this->getProgressBar()->update($itemsCount, "$message");
        if ($itemsCount == $itemsMax) {
            $this->getProgressBar()->update($itemsCount, "[$itemsCount/$itemsMax]");
            $this->getProgressBar()->finish();
        }
    }

    /**
     * @param array $config
     * @param array $options
     *
     * @return Builder
     */
    public function getBuilder(
        array $config = ['debug' => false],
        array $options = ['verbosity' => Builder::VERBOSITY_NORMAL])
    {
        if (!file_exists($this->getPath().'/'.self::CONFIG_FILE)) {
            throw new \Exception(sprintf('Config file not found in "%s"!', $this->getPath()));
        }
        // verbosity: verbose
        if ($options['verbosity'] == Builder::VERBOSITY_VERBOSE) {
            $this->verbose = true;
        }
        // verbosity: quiet
        if ($options['verbosity'] == Builder::VERBOSITY_QUIET) {
            $this->quiet = true;
        }

        try {
            $configFile = Yaml::parse(file_get_contents($this->getPath().'/'.self::CONFIG_FILE));
            $config = array_replace_recursive($configFile, $config);
            $this->builder = (new Builder($config, $this->messageCallback()))
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
     * Custom message callback function.
     */
    public function messageCallback()
    {
        return function ($code, $message = '', $itemsCount = 0, $itemsMax = 0) {
            if ($this->quiet) {
                return;
            } else {
                if (strpos($code, '_PROGRESS') !== false) {
                    if ($this->verbose) {
                        if ($itemsCount > 0) {
                            $this->wlDone(sprintf('(%u/%u) %s', $itemsCount, $itemsMax, $message));

                            return;
                        }
                        $this->wlDone("$message");
                    } else {
                        if (isset($itemsCount) && $itemsMax > 0) {
                            $this->printProgressBar($itemsCount, $itemsMax, $message);
                        } else {
                            $this->wl($message);
                        }
                    }
                } elseif (strpos($code, '_ERROR') !== false) {
                    $this->wlError($message);
                } elseif ($code == 'TIME') {
                    $this->wl($message);
                } else {
                    $this->wlAnnonce($message);
                }
            }
        };
    }

    /**
     * @param string $text
     */
    public function wl($text)
    {
        $this->console->writeLine($text);
    }

    /**
     * @param string $text
     */
    public function wlAnnonce($text)
    {
        $this->console->writeLine($text, Color::WHITE);
    }

    /**
     * @param string $text
     */
    public function wlDone($text)
    {
        $this->console->writeLine($text, Color::GREEN);
    }

    /**
     * @param string $text
     */
    public function wlAlert($text)
    {
        $this->console->writeLine($text, Color::YELLOW);
    }

    /**
     * @param string $text
     */
    public function wlError($text)
    {
        $this->console->writeLine($text, Color::RED);
    }
}
