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
use Symfony\Component\Filesystem\Filesystem;

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
            $fileSystem = new Filesystem();
            $root = __DIR__.'/../../';
            if (Plateform::isPhar()) {
                $root = Plateform::getPharPath().'/';
            }
            if (!$fileSystem->exists($this->getPath().'/'.self::CONFIG_FILE) || $this->force) {
                $fileSystem->copy($root.'skeleton/phpoole.yml', $this->getPath().'/'.self::CONFIG_FILE, true);
                $fileSystem->mirror($root.'skeleton/content', $this->getPath().'/content');
                $fileSystem->mirror($root.'skeleton/layouts', $this->getPath().'/layouts');
                $fileSystem->mirror($root.'skeleton/static', $this->getPath().'/static');
                $this->wlDone('Done!');
                exit(0);
            }
            $this->wlAlert(sprintf('File "%s" already exists.', $this->getPath().'/'.self::CONFIG_FILE));
            exit(1);
        } catch (\Exception $e) {
            $this->wlError($e->getMessage());
        }
    }
}
