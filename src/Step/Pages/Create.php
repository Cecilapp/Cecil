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

namespace Cecil\Step\Pages;

use Cecil\Collection\Page\Collection as PagesCollection;
use Cecil\Collection\Page\Page;
use Cecil\Step\AbstractStep;

/**
 * Creates Pages collection from content iterator.
 */
class Create extends AbstractStep
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Creating pages';
    }

    /**
     * {@inheritdoc}
     */
    public function init(array $options): void
    {
        $this->builder->setPages(new PagesCollection('all-pages'));

        if (is_dir($this->config->getPagesPath())) {
            $this->canProcess = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process(): void
    {
        if (\count($this->builder->getPagesFiles()) == 0) {
            return;
        }

        $total = \count($this->builder->getPagesFiles());
        $count = 0;
        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach ($this->builder->getPagesFiles() as $file) {
            $count++;
            /** @var Page $page */
            $page = new Page(Page::createIdFromFile($file));
            $page->setFile($file)->parse();

            /*
             * Apply an - optional - custom path to pages of a section.
             *
             * ```yaml
             * paths:
             *   - section: Blog
             *     language: fr # optional
             *     path: :section/:year/:month/:day/:slug
             * ```
             */
            if (\is_array($this->config->get('paths'))) {
                foreach ($this->config->get('paths') as $entry) {
                    if (isset($entry['section'])) {
                        /** @var Page $page */
                        if ($page->getSection() == Page::slugify($entry['section'])) {
                            if (isset($entry['language']) && $entry['language'] != $page->getVariable('language')) {
                                break;
                            }
                            if (isset($entry['path'])) {
                                $path = str_replace(
                                    [
                                        ':year',
                                        ':month',
                                        ':day',
                                        ':section',
                                        ':slug',
                                    ],
                                    [
                                        $page->getVariable('date')->format('Y'),
                                        $page->getVariable('date')->format('m'),
                                        $page->getVariable('date')->format('d'),
                                        $page->getSection(),
                                        $page->getSlug(),
                                    ],
                                    $entry['path']
                                );
                                $page->setPath(trim($path, '/'));
                            }
                        }
                    }
                }
            }

            // add the page to pages collection only if its language is defined in configuration
            if (\in_array($page->getVariable('language', $this->config->getLanguageDefault()), array_column($this->config->getLanguages(), 'code'))) {
                $this->builder->getPages()->add($page);
            }

            $message = sprintf('Page "%s" created', $page->getId());
            $this->builder->getLogger()->info($message, ['progress' => [$count, $total]]);
        }
    }
}
