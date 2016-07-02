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

use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem as FS;
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
     * @var FS
     */
    protected $fs;

    public function processCommand()
    {
        $this->watch = $this->getRoute()->getMatchedParam('watch', false);
        $this->fs = new FS();

        $this->setUpServer();
        $command = sprintf(
            'php -S %s:%d -t %s %s',
            'localhost',
            '8000',
            $this->getPath().'/'.$this->getPHPoole()->getOption('output.dir'),
            sprintf('%s/router.php', $this->getPath())
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
                        $this->getPath().'/'.$this->getPHPoole()->getOption('content.dir'),
                        $this->getPath().'/'.$this->getPHPoole()->getOption('layouts.dir'),
                    ]);
                if (is_dir($this->getPath().'/'.$this->getPHPoole()->getOption('themes.dir'))) {
                    $finder->in($this->getPath().'/'.$this->getPHPoole()->getOption('themes.dir'));
                }
                $rc = new ResourceCacheMemory();
                $rw = new ResourceWatcher($rc);
                $rw->setFinder($finder);
                $this->fs->dumpFile($this->getPath().'/.watch.flag', '');
            }
            // start server
            try {
                $process->start();
                while ($process->isRunning()) {
                    // watch changes?
                    if ($this->watch) {
                        $rw->findChanges();
                        if ($rw->hasChanges()) {
                            $this->fs->dumpFile($this->getPath().'/.changes.flag', '');
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
            if (isPhar()) {
                $root = isPhar().'/';
            }
            $this->fs->copy($root.'res/router.php', $this->getPath().'/router.php', true);
            $this->fs->copy($root.'res/livereload.js', $this->getPath().'/livereload.js', true);
            $this->fs->dumpFile($this->getPath().'/.baseurl', $this->getPHPoole()->getOption('site.baseurl'));
        } catch (IOExceptionInterface $e) {
            echo 'An error occurred while copying file at '.$e->getPath().PHP_EOL;
            echo $e->getMessage().PHP_EOL;
            exit(2);
        }
        if (!is_file(sprintf('%s/router.php', $this->getPath()))) {
            $this->wlError('Router not found');
            exit(2);
        }
    }

    public function tearDownServer()
    {
        try {
            $this->fs->remove([
                $this->getPath().'/router.php',
                $this->getPath().'/livereload.js',
                $this->getPath().'/.baseurl',
            ]);
        } catch (IOExceptionInterface $e) {
            echo $e->getMessage().PHP_EOL;
        }
    }
}
