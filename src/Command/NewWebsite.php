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

use Symfony\Component\Filesystem\Filesystem as FS;

class NewWebsite extends AbstractCommand
{
    /**
     * @var bool
     */
    protected $force;

    public function processCommand()
    {
        $this->force = $this->getRoute()->getMatchedParam('force', false);

        $this->wlAnnonce('Creates a new website...');
        try {
            $fs = new FS();
            $root = '';
            if (empty(\Phar::running())) {
                $root = __DIR__.'/../../';
            }
            if (!$fs->exists($this->getPath().'/'.self::CONFIG_FILE) || $this->force) {
                $fs->copy($root.'skeleton/phpoole.yml', $this->getPath().'/'.self::CONFIG_FILE, true);
                $fs->mirror($root.'skeleton/content', $this->getPath().'/content');
                $fs->mirror($root.'skeleton/layouts', $this->getPath().'/layouts');
                $fs->mirror($root.'skeleton/static', $this->getPath().'/static');
                $fs->mirror($root.'skeleton/themes', $this->getPath().'/themes');

                $this->wlDone('Done!');
            } else {
                $this->wlAlert(sprintf('File "%s" already exists.', $this->getPath().'/'.self::CONFIG_FILE));
            }
        } catch (\Exception $e) {
            $this->wlError($e->getMessage());
        }
    }
}
