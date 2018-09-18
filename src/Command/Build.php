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
     * @var bool
     */
    protected $verbose;
    /**
     * @var bool
     */
    protected $quiet;
    /**
     * @var bool
     */
    protected $remove;
    /**
     * @var bool
     */
    protected $dryrun;

    public function processCommand()
    {
        $this->drafts = $this->route->getMatchedParam('drafts', false);
        $this->baseurl = $this->route->getMatchedParam('baseurl');
        $this->verbose = $this->route->getMatchedParam('verbose', false);
        $this->quiet = $this->route->getMatchedParam('quiet', false);
        $this->remove = $this->getRoute()->getMatchedParam('remove', false);
        $this->dryrun = $this->getRoute()->getMatchedParam('dry-run', false);

        $config = [];
        $options = [];
        $messageOpt = '';

        if ($this->baseurl) {
            $config['site']['baseurl'] = $this->baseurl;
        }
        if ($this->dryrun) {
            $options['dry-run'] = true;
            $messageOpt .= ' dry-run';
        }
        if ($this->drafts) {
            $options['drafts'] = true;
            $messageOpt .= ' with drafts';
        }
        if ($this->verbose) {
            $options['verbosity'] = PHPoole::VERBOSITY_VERBOSE;
        } else {
            if ($this->quiet) {
                $options['verbosity'] = PHPoole::VERBOSITY_QUIET;
            }
        }
        if ($this->remove) {
            if ($this->fs->exists($this->getPath().'/'.$this->getPHPoole()->getConfig()->get('output.dir'))) {
                $this->fs->remove($this->getPath().'/'.$this->getPHPoole()->getConfig()->get('output.dir'));
                $this->wlDone('Output directory removed!');
                exit(0);
            }
            $this->wlError('Output directory not found!');
            exit(0);
        }

        try {
            if (!$this->quiet) {
                $this->wl(sprintf('Building website%s...', $messageOpt));
            }
            $this->getPHPoole($config, $options)->build($options);
            if ($this->getRoute()->getName() == 'serve') {
                $this->fs->dumpFile($this->getPath().'/.phpoole/changes.flag', '');
            }
        } catch (\Exception $e) {
            throw new \Exception(sprintf($e->getMessage()));
        }
    }
}
