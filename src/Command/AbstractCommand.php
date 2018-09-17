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
use Zend\Console\Prompt\Confirm;
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
     * @var bool
     */
    protected $debug = false;
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
    protected function newPB($start, $max)
    {
        if ($this->progressBar === null || $max != $this->pbMax) {
            $this->pbMax = $max;
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
        // debug mode?
        if (array_key_exists('debug', $options) && $options['debug']) {
            $this->debug = true;
        }
        // quiet mode?
        if (array_key_exists('verbosity', $options) && $options['verbosity'] == PHPoole::VERBOSITY_QUIET) {
            $this->quiet = true;
        }

        // CLI custom message callback function
        $messageCallback = function ($code, $message = '', $itemsCount = 0, $itemsMax = 0) {
            switch ($code) {
                case 'LOCATE':
                case 'CREATE':
                case 'CONVERT':
                case 'GENERATE':
                case 'MENU':
                case 'COPY':
                case 'RENDER':
                case 'SAVE':
                    if (!$this->quiet) {
                        $this->wlAnnonce($message);
                    }
                    break;
                case 'TIME':
                    if (!$this->quiet) {
                        $this->wl($message);
                    }
                    break;
                case 'LOCATE_PROGRESS':
                case 'CREATE_PROGRESS':
                case 'CONVERT_PROGRESS':
                case 'GENERATE_PROGRESS':
                case 'MENU_PROGRESS':
                case 'COPY_PROGRESS':
                case 'RENDER_PROGRESS':
                case 'SAVE_PROGRESS':
                    if ($this->debug) {
                        if ($itemsCount > 0) {
                            $this->wlDone(sprintf('(%u/%u) %s', $itemsCount, $itemsMax, $message));
                            break;
                        }
                        $this->wlDone("$message");
                    } else {
                        if (!$this->quiet) {
                            if (isset($itemsCount) && $itemsMax > 0) {
                                $this->newPB(0, $itemsMax);
                                $this->getPB()->update($itemsCount, "$message");
                                if ($itemsCount == $itemsMax) {
                                    $this->getPB()->update($itemsCount, "[$itemsCount/$itemsMax]");
                                    $this->getPB()->finish();
                                }
                            } else {
                                $this->wl($message);
                            }
                        }
                    }
                    break;
                case 'LOCATE_ERROR':
                case 'CREATE_ERROR':
                case 'CONVERT_ERROR':
                case 'GENERATE_ERROR':
                case 'MENU_ERROR':
                case 'COPY_ERROR':
                case 'RENDER_ERROR':
                case 'SAVE_ERROR':
                    $this->wlError($message);
                    break;
            }
        };

        if (!file_exists($this->getPath().'/'.self::CONFIG_FILE)) {
            throw new \Exception(sprintf('Config file not found in "%s"!', $this->getPath()));
        }

        try {
            $configFile = Yaml::parse(file_get_contents($this->getPath().'/'.self::CONFIG_FILE));
            $this->phpoole = new PHPoole($configFile, $messageCallback);
            $this->phpoole->setSourceDir($this->getPath());
            $this->phpoole->setDestinationDir($this->getPath());
        } catch (ParseException $e) {
            throw new \Exception(sprintf('Config file parse error: %s', $e->getMessage()));
        } catch (\Exception $e) {
            throw new \Exception(sprintf($e->getMessage()));
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
