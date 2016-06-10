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
use PHPoole\Util;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;
use Yosymfony\ResourceWatcher\ResourceCacheFile;
use Yosymfony\ResourceWatcher\ResourceWatcher;

class Serve extends AbstractCommand
{
    /**
     * @var bool
     */
    protected $_watch;

    public function processCommand()
    {
        $this->_watch = $this->getRoute()->getMatchedParam('watch', false);

        if (!is_file(sprintf('%s/router.php', $this->getPath()))) {
            $this->wlError('Router not found');
            exit(2);
        }
        $this->wlAnnonce(sprintf('Start server http://%s:%d', 'localhost', '8000'));
        $command = sprintf(
            'php -S %s:%d -t %s %s',
            'localhost',
            '8000',
            $this->getPath().'/'.PHPoole::SITE_SRV_DIRNAME,
            sprintf('%s/router.php', $this->getPath())
        );
        $process = new Process($command);
        if (!$process->isStarted()) {
            if ($this->_watch) {
                $finder = new Finder();
                $finder->files()
                    ->name('*.md')
                    ->name('*.html')
                    ->in([
                        $this->getPath().'/'.PHPoole::CONTENT_DIRNAME,
                        $this->getPath().'/'.PHPoole::LAYOUTS_DIRNAME,
                    ]);
                $rc = new ResourceCacheFile($this->getPath().'/.cache.php');
                $rw = new ResourceWatcher($rc);
                $rw->setFinder($finder);
                Util::writeFile($this->getPath().'/.watch', '');
            }
            $process->start();
            while ($process->isRunning()) {
                if ($this->_watch) {
                    $rw->findChanges();
                    if ($rw->hasChanges()) {
                        Util::writeFile($this->getPath().'/.changes', 'true');
                        $this->wlDone('write "changes" flag file');
                        // re-generate
                        $this->wlAlert('Changes detected: re-build');
                        $callable = new Build();
                        call_user_func($callable, $this->getRoute(), $this->getConsole());
                    }
                }
                usleep(1000000); // 1 s
            }
        }
    }
}
