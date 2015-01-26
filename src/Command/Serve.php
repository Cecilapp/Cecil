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

use PHPoole\Command\AbstractCommand;
use PHPoole\PHPoole;
use PHPoole\Util;
use Symfony\Component\Process\Process;
use Symfony\Component\Finder\Finder;
use Yosymfony\ResourceWatcher\ResourceWatcher;
use Yosymfony\ResourceWatcher\ResourceCacheFile;

class Serve extends AbstractCommand
{
    /**
     * @var bool
     */
    protected $_watch;

    public function processCommand()
    {
        $this->_watch = $this->_route->getMatchedParam('watch', false);

        if (!is_file(sprintf('%s/router.php', $this->_path))) {
            $this->wlError('Router not found');
            exit(2);
        }
        $this->wlAnnonce(sprintf("Start server http://%s:%d", 'localhost', '8000'));
        $command = sprintf(
            'php -S %s:%d -t %s %s',
            'localhost',
            '8000',
            $this->_path . '/' . PHPoole::SITE_SRV_DIRNAME,
            sprintf('%s/router.php', $this->_path)
        );
        $process = new Process($command);
        if (!$process->isStarted()) {
            if ($this->_watch) {
                $finder = new Finder();
                $finder->files()
                    ->name('*.md')
                    ->in($this->_path . '/' . PHPoole::CONTENT_DIRNAME);
                $rc = new ResourceCacheFile($this->_path . '/.cache.php');
                $rw = new ResourceWatcher($rc);
                $rw->setFinder($finder);
                Util::writeFile($this->_path . '/.watch', '');
            }
            $process->start();
            while ($process->isRunning()) {
                if ($this->_watch) {
                    $rw->findChanges();
                    if ($rw->hasChanges()) {
                        Util::writeFile($this->_path . '/.changes', 'true');
                        $this->wlDone('write "changes" flag file');
                        // re-generate
                        $this->wlAlert('Changes detected: re-generate');
                        $callable = new Generate;
                        call_user_func($callable, $this->_route, $this->_console);
                    }
                }
                usleep(1000000); // 1 s
            }
        }
    }
}