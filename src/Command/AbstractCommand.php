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
use Symfony\Component\Yaml\Yaml;
use Zend\Console\Adapter\AdapterInterface as Console;
use Zend\Console\ColorInterface as Color;
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
            $this->wlError('Invalid directory provided!');
            exit(2);
        }
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
     * @return PHPoole
     */
    public function getPHPoole()
    {
        $messageCallback = function ($code, $message = '', $itemsCount = 0, $itemsMax = 0, $verbose = true) {
            switch ($code) {
                case 'CREATE':
                case 'CONVERT':
                case 'GENERATE':
                case 'RENDER':
                case 'COPY':
                    $this->wlAnnonce($message);
                    break;
                case 'CREATE_PROGRESS':
                case 'CONVERT_PROGRESS':
                case 'GENERATE_PROGRESS':
                case 'RENDER_PROGRESS':
                case 'COPY_PROGRESS':
                    if ($itemsCount > 0 && $verbose !== false) {
                        $this->wlDone(sprintf("\r  (%u/%u) %s", $itemsCount, $itemsMax, $message));
                    } else {
                        $this->wlDone("  $message");
                    }
                    break;
            }
        };

        if (!$this->phpoole instanceof PHPoole) {
            if (!file_exists($this->getPath().'/'.self::CONFIG_FILE)) {
                $this->wlError('Config file (phpoole.yml) not found!');
                exit(2);
            }
            try {
                $options = Yaml::parse(file_get_contents($this->getPath().'/'.self::CONFIG_FILE));
                $this->phpoole = new PHPoole($options, $messageCallback);
                $this->phpoole->setSourceDir($this->getPath());
                $this->phpoole->setDestDir($this->getPath());
            } catch (\Exception $e) {
                $this->wlError($e->getMessage());
                exit(2);
            }
        }

        return $this->phpoole;
    }

    /**
     * @param $text
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
     * @param $text
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
