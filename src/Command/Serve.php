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

use Cecil\Util\Plateform;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Yosymfony\ResourceWatcher\Crc32ContentHash;
use Yosymfony\ResourceWatcher\ResourceCacheMemory;
use Yosymfony\ResourceWatcher\ResourceWatcher;

class Serve extends AbstractCommand
{
    /**
     * @var string
     */
    public static $tmpDir = '.cecil';
    /**
     * @var bool
     */
    protected $open;

    public function processCommand()
    {
        $this->open = $this->getRoute()->getMatchedParam('open', false);

        $this->setUpServer();
        $command = sprintf(
            'php -S %s:%d -t %s %s',
            'localhost',
            '8000',
            $this->getPath().'/'.$this->getPHPoole()->getConfig()->get('output.dir'),
            sprintf('%s/%s/router.php', $this->getPath(), self::$tmpDir)
        );
        $process = new Process($command);

        // (re)build before serve
        $callable = new Build();
        $callable($this->getRoute(), $this->getConsole());

        // handle process
        if (!$process->isStarted()) {
            // write changes cache
            $finder = new Finder();
            $finder->files()
                ->name('*.md')
                ->name('*.twig')
                ->name('*.yml')
                ->name('*.css')
                ->name('*.scss')
                ->name('*.js')
                ->in($this->getPath())
                ->exclude($this->getPHPoole()->getConfig()->get('output.dir'));
            $hashContent = new Crc32ContentHash();
            $resourceCache = new ResourceCacheMemory();
            $resourceWatcher = new ResourceWatcher($resourceCache, $finder, $hashContent);
            $resourceWatcher->initialize();
            // start server
            try {
                $this->wlAnnonce(sprintf('Starting server (http://%s:%d)...', 'localhost', '8000'));
                $process->start();
                if ($this->open) {
                    Plateform::openBrowser('http://localhost:8000');
                }
                while ($process->isRunning()) {
                    $result = $resourceWatcher->findChanges();
                    if ($result->hasChanges()) {
                        // re-build
                        $this->wlAlert('Changes detected!');
                        $callable($this->getRoute(), $this->getConsole());
                    }
                    usleep(1000000); // wait 1s
                }
            } catch (ProcessFailedException $e) {
                $this->tearDownServer();

                throw new \Exception(sprintf($e->getMessage()));
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
            $this->fs->copy($root.'res/server/router.php', $this->getPath().'/'.self::$tmpDir.'/router.php', true);
            $this->fs->copy($root.'res/server/livereload.js', $this->getPath().'/'.self::$tmpDir.'/livereload.js', true);
            $this->fs->dumpFile(
                $this->getPath().'/'.self::$tmpDir.'/baseurl',
                $this->getPHPoole()->getConfig()->get('site.baseurl')
            );
        } catch (IOExceptionInterface $e) {
            throw new \Exception(sprintf('An error occurred while copying file at "%s"', $e->getPath()));
        }
        if (!is_file(sprintf('%s/%s/router.php', $this->getPath(), self::$tmpDir))) {
            throw new \Exception(sprintf('Router not found: "./%s/router.php"', self::$tmpDir));
        }
    }

    public function tearDownServer()
    {
        try {
            $this->fs->remove($this->getPath().'/'.self::$tmpDir);
        } catch (IOExceptionInterface $e) {
            throw new \Exception(sprintf($e->getMessage()));
        }
    }
}
