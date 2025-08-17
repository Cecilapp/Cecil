<?php

/**
 * This file is part of Cecil.
 *
 * (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cecil\Step\Pages;

use Cecil\Collection\Page\Page;
use Cecil\Exception\RuntimeException;
use Cecil\Renderer\Page as PageRenderer;
use Cecil\Step\AbstractStep;
use Cecil\Util;

/**
 * Save step.
 *
 * This step is responsible for saving the rendered pages to the output directory.
 * It iterates through the pages, checks if they have been rendered, and saves
 * the output in the specified format. The saved files are logged, and the
 * output directory is created if it does not exist. If the `dry-run` option is
 * enabled, the step will not perform any file operations but will still log
 * the intended actions.
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
        if ($options['dry-run']) {
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
        $filteredPages = $this->builder->getPages()->filter(function (Page $page) {
            return !empty($page->getRendered());
        });
        $total = \count($filteredPages);

        $count = 0;
        foreach ($filteredPages as $page) {
            $count++;
            $files = [];

            foreach ($page->getRendered() as $format => $rendered) {
                if (false === $pathname = (new PageRenderer($this->config))->getOutputFilePath($page, $format)) {
                    throw new RuntimeException(\sprintf("Can't get pathname of page '%s' (format: '%s').", $page->getId(), $format));
                }
                $pathname = $this->cleanPath(Util::joinFile($this->config->getOutputPath(), $pathname));

                try {
                    Util\File::getFS()->dumpFile($pathname, $rendered['output']);
                } catch (\Symfony\Component\Filesystem\Exception\IOException $e) {
                    throw new RuntimeException($e->getMessage());
                }

                $files[] = $this->builder->isDebug() ? $pathname : substr($pathname, \strlen($this->config->getOutputPath()) + 1);
            }

            $message = \sprintf('File(s) "%s" saved', implode(', ', $files));
            $this->builder->getLogger()->info($message, ['progress' => [$count, $total]]);
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
}
