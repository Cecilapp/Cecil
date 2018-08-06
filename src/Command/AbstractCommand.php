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
            exit(2);
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
     * @param array $options
     *
     * @return PHPoole
     */
    public function getPHPoole(array $options = [])
    {
        $messageCallback = function ($code, $message = '', $itemsCount = 0, $itemsMax = 0, $verbose = true) {
            switch (true) {
                case $code == 'CREATE'
                || $code == 'CONVERT'
                || $code == 'GENERATE'
                || $code == 'RENDER'
                || $code == 'COPY':
                    $this->wlAnnonce($message);
                    break;
                case $code == 'CREATE_PROGRESS'
                || $code == 'CONVERT_PROGRESS'
                || $code == 'GENERATE_PROGRESS'
                || $code == 'RENDER_PROGRESS'
                || $code == 'COPY_PROGRESS':
                    if ($itemsCount > 0 && $verbose !== false) {
                        $this->wlDone(sprintf("\r  (%u/%u) %s", $itemsCount, $itemsMax, $message));
                        break;
                    }
                    $this->wlDone("  $message");
                    break;
            }
        };

        if (!$this->phpoole instanceof PHPoole) {
            if (!file_exists($this->getPath().'/'.self::CONFIG_FILE)) {
                throw new \Exception(sprintf('Config file not found in "%s"!', $this->getPath()));
                exit(2);
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
                exit(1);
            } catch (\Exception $e) {
                throw new \Exception(sprintf($e->getMessage()));
                exit(1);
            }
        }

        return $this->phpoole;
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
