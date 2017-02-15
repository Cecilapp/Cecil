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

use PHPoole\Util\Plateform;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Yosymfony\ResourceWatcher\ResourceCacheMemory;
use Yosymfony\ResourceWatcher\ResourceWatcher;

class Serve extends AbstractCommand
{
    /**
     * @var bool
     */
    protected $watch;
    /**
     * @var Filesystem
     */
    protected $fileSystem;

    public function processCommand()
    {
        $this->watch = $this->getRoute()->getMatchedParam('watch', false);
        $this->fileSystem = new Filesystem();

        $this->setUpServer();
        $command = sprintf(
            'php -S %s:%d -t %s %s',
            'localhost',
            '8000',
            $this->getPath().'/'.$this->getPHPoole()->getConfig()->get('output.dir'),
            sprintf('%s/.phpoole/router.php', $this->getPath())
        );

        $this->wlAnnonce(sprintf('Starting server (http://%s:%d)...', 'localhost', '8000'));
        $process = new Process($command);
        if (!$process->isStarted()) {
            // write changes cache
            if ($this->watch) {
                $finder = new Finder();
                $finder->files()
                    ->name('*.md')
                    ->name('*.html')
                    ->in([
                        $this->getPath().'/'.$this->getPHPoole()->getConfig()->get('content.dir'),
                        $this->getPath().'/'.$this->getPHPoole()->getConfig()->get('layouts.dir'),
                    ]);
                if (is_dir($this->getPath().'/'.$this->getPHPoole()->getConfig()->get('themes.dir'))) {
                    $finder->in($this->getPath().'/'.$this->getPHPoole()->getConfig()->get('themes.dir'));
                }
                $resourceCache = new ResourceCacheMemory();
                $resourceWatcher = new ResourceWatcher($resourceCache);
                $resourceWatcher->setFinder($finder);
                $this->fileSystem->dumpFile($this->getPath().'/.phpoole/watch.flag', '');
            }
            // start server
            try {
                $process->start();
                Plateform::openBrowser('http://localhost:8000');
                while ($process->isRunning()) {
                    // watch changes?
                    if ($this->watch) {
                        $resourceWatcher->findChanges();
                        if ($resourceWatcher->hasChanges()) {
                            $this->fileSystem->dumpFile($this->getPath().'/.phpoole/changes.flag', '');
                            // re-generate
                            $this->wlAlert('Changes detected!');
                            $callable = new Build();
                            call_user_func($callable, $this->getRoute(), $this->getConsole());
                        }
                    }
                    usleep(1000000); // 1 s
                }
            } catch (ProcessFailedException $e) {
                $this->tearDownServer();
                echo $e->getMessage();
                exit(2);
            }
        }
    }

    public function setUpServer()
    {
        try {
            $root = __DIR__.'/../../';
            if (Plateform::isPhar()) {
                $root = Plateform::getPharPath().'/';
            }
            $this->fileSystem->copy($root.'res/router.php', $this->getPath().'/.phpoole/router.php', true);
            $this->fileSystem->copy($root.'res/livereload.js', $this->getPath().'/.phpoole/livereload.js', true);
            $this->fileSystem->dumpFile($this->getPath().'/.phpoole/baseurl', $this->getPHPoole()->getConfig()->get('site.baseurl'));
        } catch (IOExceptionInterface $e) {
            echo 'An error occurred while copying file at '.$e->getPath().PHP_EOL;
            echo $e->getMessage().PHP_EOL;
            exit(2);
        }
        if (!is_file(sprintf('%s/.phpoole/router.php', $this->getPath()))) {
            $this->wlError('Router not found');
            exit(2);
        }
    }

    public function tearDownServer()
    {
        try {
            $this->fileSystem->remove([
                $this->getPath().'/.phpoole/router.php',
                $this->getPath().'/.phpoole/livereload.js',
                $this->getPath().'/.phpoole/baseurl',
            ]);
        } catch (IOExceptionInterface $e) {
            echo $e->getMessage().PHP_EOL;
        }
    }
}
