<?php
/**
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Step;

use cecil\Util;
use Symfony\Component\Finder\Finder;

/**
 * Loads static files.
 */
class StaticLoad extends AbstractStep
{
    /**
     * {@inheritdoc}
     */
    public function init($options)
    {
        /** @var \Cecil\Builder $builder */
        /** @var \Cecil\Config $config */
        if (is_dir($this->builder->getConfig()->getStaticPath()) && $this->config->get('static.load')) {
            $this->process = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process()
    {
        $this->builder->getLogger()->debug('Loading static files');

        $files = Finder::create()
            ->files()
            ->in($this->builder->getConfig()->getStaticPath());
        if (is_array($this->config->get('static.exclude'))) {
            $files->notName($this->config->get('static.exclude'));
        }
        $files->sortByName(true);
        $max = count($files);

        if ($max <= 0) {
            $message = 'No files';
            $this->builder->getLogger()->debug($message);

            return;
        }

        $staticFiles = [];
        $count = 0;
        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach ($files as $file) {
            $staticFiles[$count]['file'] = $file->getRelativePathname();
            $staticFiles[$count]['path'] = Util::joinPath($file->getRelativePathname());
            $staticFiles[$count]['date'] = (new \DateTime())->setTimestamp($file->getCTime());
            $staticFiles[$count]['updated'] = (new \DateTime())->setTimestamp($file->getMTime());
            $staticFiles[$count]['name'] = $file->getBasename();
            $staticFiles[$count]['basename'] = $file->getBasename('.'.$file->getExtension());
            $staticFiles[$count]['ext'] = $file->getExtension();
            $count++;

            $message = sprintf('%s', $file->getRelativePathname());
            $this->builder->getLogger()->debug($message, ['progress' => [$count, $max]]);
        }

        $this->builder->setStatic($staticFiles);
    }
}
