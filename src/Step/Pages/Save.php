<?php declare(strict_types=1);

/*
 * This file is part of Cecil.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Step\Pages;

use Cecil\Collection\Page\Page;
use Cecil\Exception\RuntimeException;
use Cecil\Renderer\Page as PageRenderer;
use Cecil\Step\AbstractStep;
use Cecil\Util;

/**
 * Pages saving.
 */
class Save extends AbstractStep
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Saving pages';
    }

    /**
     * {@inheritdoc}
     */
    public function init(array $options): void
    {
        // clear cache?
        $this->clearCache();

        if ($options['dry-run']) {
            $this->canProcess = false;

            return;
        }

        Util\File::getFS()->mkdir($this->config->getOutputPath());

        $this->canProcess = true;
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     */
    public function process(): void
    {
        /** @var Page $page */
        $filteredPages = $this->builder->getPages()->filter(function (Page $page) {
            return !empty($page->getVariable('rendered'));
        });
        $max = count($filteredPages);

        $count = 0;
        foreach ($filteredPages as $page) {
            $count++;
            $files = [];

            foreach ($page->getVariable('rendered') as $format => $rendered) {
                if (false === $pathname = (new PageRenderer($this->config))->getOutputFile($page, $format)) {
                    throw new RuntimeException(\sprintf("Can't get pathname of page '%s' (format: '%s')", $page->getId(), $format));
                }
                $pathname = $this->cleanPath(Util::joinFile($this->config->getOutputPath(), $pathname));

                try {
                    Util\File::getFS()->dumpFile($pathname, $rendered['output']);
                } catch (\Symfony\Component\Filesystem\Exception\IOException $e) {
                    throw new RuntimeException($e->getMessage());
                }

                $files[] = substr($pathname, strlen($this->config->getDestinationDir()) + 1);
            }

            $message = \sprintf('File(s) "%s" saved', implode(', ', $files));
            $this->builder->getLogger()->info($message, ['progress' => [$count, $max]]);
        }
    }

    /**
     * Removes unnecessary directory separators.
     */
    private function cleanPath(string $pathname): string
    {
        if (DIRECTORY_SEPARATOR == '\\') {
            $pathname = preg_replace('#\\\\+#', '\\', $pathname);
        }

        return preg_replace('#/+#', '/', $pathname);
    }

    /**
     * Deletes cache directory.
     */
    private function clearCache(): void
    {
        if ($this->config->get('cache.enabled') === false) {
            Util\File::getFS()->remove($this->config->getCachePath());
        }
    }
}
