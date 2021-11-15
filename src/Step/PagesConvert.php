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

use Cecil\Collection\Page\Page;
use Cecil\Converter\Converter;
use Cecil\Exception\Exception;

/**
 * Converts content of all pages.
 */
class PagesConvert extends AbstractStep
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Converting pages';
    }

    /**
     * {@inheritdoc}
     */
    public function init($options)
    {
        /** @var \Cecil\Builder $builder */
        if (is_dir($this->builder->getConfig()->getContentPath())) {
            $this->canProcess = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process()
    {
        if (count($this->builder->getPages()) <= 0) {
            return;
        }

        if ($this->builder->getBuildOptions()['drafts']) {
            $this->builder->getLogger()->info('Drafts included');
        }

        $max = count($this->builder->getPages());
        $count = 0;
        /** @var Page $page */
        foreach ($this->builder->getPages() as $page) {
            if (!$page->isVirtual()) {
                $count++;

                try {
                    $convertedPage = $this->convertPage($page, (string) $this->config->get('frontmatter.format'));
                    // set default language
                    if (!$convertedPage->hasVariable('language')) {
                        $convertedPage->setVariable('language', $this->config->getLanguageDefault());
                    }
                } catch (Exception $e) {
                    $this->builder->getPages()->remove($page->getId());
                    $this->builder->getLogger()->error(
                        sprintf('Unable to convert page "%s"', $page->getId())
                    );
                    $this->builder->getLogger()->debug(
                        sprintf('Page "%s": %s', $page->getId(), $e->getMessage())
                    );
                    continue;
                }

                /**
                 * Apply a custom path to pages of a specified section.
                 *
                 * ie:
                 * paths:
                 * - section: Blog
                 *   path: :section/:year/:month/:day/:slug
                 */
                if (is_array($this->config->get('paths'))) {
                    foreach ($this->config->get('paths') as $entry) {
                        if (array_key_exists('section', $entry)) {
                            /** @var Page $page */
                            if ($page->getSection() == Page::slugify($entry['section'])) {
                                if (array_key_exists('path', $entry)) {
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
                                    $page->setPath($path);
                                }
                            }
                        }
                    }
                }

                $message = $page->getId();
                // forces drafts convert?
                if ($this->builder->getBuildOptions()['drafts']) {
                    $page->setVariable('published', true);
                }
                if ($page->getVariable('published')) {
                    $this->builder->getPages()->replace($page->getId(), $convertedPage);
                } else {
                    $message .= ' (not published)';
                }
                $this->builder->getLogger()->info($message, ['progress' => [$count, $max]]);
            }
        }
    }

    /**
     * Converts page content:
     * - Yaml frontmatter to PHP array
     * - Markdown body to HTML.
     *
     * @throws Exception
     */
    public function convertPage(Page $page, $format = 'yaml'): Page
    {
        // converts frontmatter
        if ($page->getFrontmatter()) {
            try {
                $variables = (new Converter($this->builder))->convertFrontmatter($page->getFrontmatter(), $format);
            } catch (\Exception $e) {
                throw new Exception($e->getMessage());
            }
            $page->setFmVariables($variables);
            $page->setVariables($variables);
        }

        // converts body
        $html = (new Converter($this->builder))->convertBody($page->getBody());
        $page->setBodyHtml($html);

        return $page;
    }
}
