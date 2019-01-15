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

use Cecil\Builder;

/**
 * Class Build.
 */
class Build extends AbstractCommand
{
    /**
     * @var bool
     */
    protected $drafts;
    /**
     * @var bool
     */
    protected $verbose;
    /**
     * @var bool
     */
    protected $quiet;
    /**
     * @var string
     */
    protected $baseurl;
    /**
     * @var destination
     */
    protected $destination;
    /**
     * @var bool
     */
    protected $dryrun;

    /**
     * {@inheritdoc}
     */
    public function processCommand()
    {
        $this->drafts = $this->getRoute()->getMatchedParam('drafts', false);
        $this->verbose = $this->getRoute()->getMatchedParam('verbose', false);
        $this->quiet = $this->getRoute()->getMatchedParam('quiet', false);
        $this->baseurl = $this->getRoute()->getMatchedParam('baseurl');
        $this->destination = $this->getRoute()->getMatchedParam('destination');
        $this->dryrun = $this->getRoute()->getMatchedParam('dry-run', false);

        $config = [];
        $options = [];
        $messageOpt = '';

        if ($this->drafts) {
            $options['drafts'] = true;
            $messageOpt .= ' with drafts';
        }
        if ($this->verbose) {
            $options['verbosity'] = Builder::VERBOSITY_VERBOSE;
        } else {
            if ($this->quiet) {
                $options['verbosity'] = Builder::VERBOSITY_QUIET;
            }
        }
        if ($this->baseurl) {
            $config['site']['baseurl'] = $this->baseurl;
        }
        if ($this->destination) {
            $config['output']['dir'] = $this->destination;
            $this->fs->dumpFile($this->getPath().'/'.Serve::$tmpDir.'/output', $this->destination);
        }
        if ($this->dryrun) {
            $options['dry-run'] = true;
            $messageOpt .= ' dry-run';
        }

        try {
            if (!$this->quiet) {
                $this->wl(sprintf('Building website%s...', $messageOpt));
            }
            $this->getBuilder($config, $options)->build($options);
            if ($this->getRoute()->getName() == 'serve') {
                $this->fs->dumpFile($this->getPath().'/'.Serve::$tmpDir.'/changes.flag', '');
            }
        } catch (\Exception $e) {
            throw new \Exception(sprintf('%s', $e->getMessage()));
        }
    }
}
