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

use Symfony\Component\Filesystem\Filesystem;

class Build extends AbstractCommand
{
    /**
     * @var bool
     */
    protected $drafts;
    /**
     * @var string
     */
    protected $baseurl;
    /**
     * @var Filesystem
     */
    protected $fileSystem;

    public function processCommand()
    {
        $this->drafts = $this->route->getMatchedParam('drafts', false);
        $this->baseurl = $this->route->getMatchedParam('baseurl');
        $this->fileSystem = new Filesystem();

        $this->wlAnnonce('Building website...');

        try {
            $options = [];
            if ($this->drafts) {
                $options['drafts'] = true;
            }
            if ($this->baseurl) {
                $options['site']['baseurl'] = $this->baseurl;
            }
            $this->getPHPoole($options)->build();
            $this->fileSystem->dumpFile($this->getPath().'/.phpoole/changes.flag', '');
        } catch (\Exception $e) {
            $this->wlError($e->getMessage());
        }
    }
}
