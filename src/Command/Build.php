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

    public function processCommand()
    {
        $this->drafts = $this->route->getMatchedParam('drafts', false);
        $this->baseurl = $this->route->getMatchedParam('baseurl');

        $message = 'Building website%s...';

        $options = [];
        if ($this->drafts) {
            $options['drafts'] = true;
            $messageOpt = ' (with drafts)';
        }
        if ($this->baseurl) {
            $options['site']['baseurl'] = $this->baseurl;
        }

        $this->wlAnnonce(sprintf($message, $messageOpt));

        try {
            $this->getPHPoole($options)->build();
            $this->fs->dumpFile($this->getPath().'/.phpoole/changes.flag', '');
        } catch (\Exception $e) {
            throw new \Exception(sprintf($e->getMessage()));
            exit(1);
        }
    }
}
