<?php

declare(strict_types=1);

/*
 * This file is part of Cecil.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Step\StaticFiles;

use Cecil\Step\AbstractStep;
use Cecil\Util;
use Symfony\Component\Finder\Finder;
use wapmorgan\Mp3Info\Mp3Info;

/**
 * Loads static files.
 */
class Load extends AbstractStep
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Loading static files';
    }

    /**
     * {@inheritdoc}
     */
    public function init(array $options): void
    {
        if (is_dir($this->config->getStaticPath()) && $this->config->isEnabled('static.load')) {
            $this->canProcess = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process(): void
    {
        $files = Finder::create()
            ->files()
            ->in($this->config->getStaticPath());
        if (\is_array($exclude = $this->config->get('static.exclude'))) {
            $files->notName($exclude);
        }
        $files->sortByName(true);
        $total = \count($files);

        if ($total < 1) {
            $message = 'No files';
            $this->builder->getLogger()->info($message);

            return;
        }

        if (\extension_loaded('exif')) {
            $this->builder->getLogger()->debug('EXIF extension is loaded');
        }

        $staticFiles = [];
        $count = 0;
        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach ($files as $file) {
            list($type, $subtype) = Util\File::getMediaType($file->getRealPath());
            $staticFiles[$count]['file'] = $file->getRelativePathname();
            $staticFiles[$count]['path'] = Util::joinPath($file->getRelativePathname());
            $staticFiles[$count]['date'] = (new \DateTime())->setTimestamp($file->getCTime());
            $staticFiles[$count]['updated'] = (new \DateTime())->setTimestamp($file->getMTime());
            $staticFiles[$count]['name'] = $file->getBasename();
            $staticFiles[$count]['basename'] = $file->getBasename('.' . $file->getExtension());
            $staticFiles[$count]['ext'] = $file->getExtension();
            $staticFiles[$count]['type'] = $type;
            $staticFiles[$count]['subtype'] = $subtype;
            if ($subtype == 'image/jpeg') {
                $staticFiles[$count]['exif'] = Util\File::readExif($file->getRealPath());
            }
            if ($type == 'audio') {
                $staticFiles[$count]['audio'] = new Mp3Info($file->getRealPath());
            }
            $count++;

            $message = \sprintf('File "%s" loaded', $file->getRelativePathname());
            $this->builder->getLogger()->info($message, ['progress' => [$count, $total]]);
        }

        $this->builder->setStatic($staticFiles);
    }
}
