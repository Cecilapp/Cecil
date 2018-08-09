<?php
/*
 * This file is part of the PHPoole package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Command;

use PHPoole\PHPoole;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Zend\Console\Adapter\AdapterInterface as Console;
use Zend\Console\ColorInterface as Color;
use Zend\ProgressBar\ProgressBar;
use ZF\Console\Route;

abstract class AbstractCommand
{
    const CONFIG_FILE = 'phpoole.yml';

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
     * @var PHPoole
     */
    protected $phpoole;
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
    protected $pbMax = 0;

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

        $this->path = realpath($this->route->getMatchedParam('path', getcwd()));
        if (!is_dir($this->path)) {
            throw new \Exception('Invalid <path> provided!');
        }
        $this->path = str_replace(DIRECTORY_SEPARATOR, '/', $this->path);

        $this->fs = new Filesystem();

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
    protected function newPB($start, $max)
    {
        if ($this->progressBar == null || $max != $this->pbMax) {
            $this->pbMax = $max;
            $adapter = new \Zend\ProgressBar\Adapter\Console([
                'elements' => [
                    \Zend\ProgressBar\Adapter\Console::ELEMENT_PERCENT,
                    \Zend\ProgressBar\Adapter\Console::ELEMENT_BAR,
                    \Zend\ProgressBar\Adapter\Console::ELEMENT_TEXT,
                ], ]);
            $this->progressBar = new ProgressBar($adapter, $start, $max);
        }

        return $this->progressBar;
    }

    /**
     * @return ProgressBar
     */
    protected function getPB()
    {
        return $this->progressBar;
    }

    /**
     * @param array $options
     *
     * @return PHPoole
     */
    public function getPHPoole(array $options = [])
    {
        $messageCallback = function ($code, $message = '', $itemsCount = 0, $itemsMax = 0, $verbose = true) {
            switch ($code) {
                case 'CREATE':
                case 'CONVERT':
                case 'GENERATE':
                case 'COPY':
                case 'RENDER':
                case 'TIME':
                    $this->wlAnnonce($message);
                    break;
                case 'CREATE_PROGRESS':
                case 'CONVERT_PROGRESS':
                case 'GENERATE_PROGRESS':
                case 'COPY_PROGRESS':
                case 'RENDER_PROGRESS':
                    if ($itemsMax && $itemsCount) {
                        $this->newPB(1, $itemsMax);
                        $this->getPB()->update($itemsCount, "$message");
                    } else {
                        $this->wl($message);
                    }
                    break;
            }
        };

        if (!$this->phpoole instanceof PHPoole) {
            if (!file_exists($this->getPath().'/'.self::CONFIG_FILE)) {
                throw new \Exception(sprintf('Config file not found in "%s"!', $this->getPath()));
            }

            try {
                $optionsFile = Yaml::parse(file_get_contents($this->getPath().'/'.self::CONFIG_FILE));
                if (is_array($options)) {
                    $options = array_replace_recursive($optionsFile, $options);
                }
                $this->phpoole = new PHPoole($options, $messageCallback);
                $this->phpoole->setSourceDir($this->getPath());
                $this->phpoole->setDestinationDir($this->getPath());
            } catch (ParseException $e) {
                throw new \Exception(sprintf('Config file parse error: %s', $e->getMessage()));
            } catch (\Exception $e) {
                throw new \Exception(sprintf($e->getMessage()));
            }
        }

        return $this->phpoole;
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
     * @param $text
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
     * @param $text
     */
    public function wlError($text)
    {
        $this->console->writeLine($text, Color::RED);
    }
}
