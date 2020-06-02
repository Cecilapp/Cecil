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
            $message = 'drafts included';
            $this->builder->getLogger()->info($message);
        }

        $max = count($this->builder->getPages());
        $count = 0;
        /** @var Page $page */
        foreach ($this->builder->getPages() as $page) {
            if (!$page->isVirtual()) {
                $count++;

                try {
                    $convertedPage = $this->convertPage($page, (string) $this->config->get('frontmatter.format'));
                } catch (Exception $e) {
                    $this->builder->getPages()->remove($page->getId());

                    $message = sprintf('Unable to convert front matter of page "%s"', $page->getId());
                    $this->builder->getLogger()->error($message);
                    $message = sprintf('Page "%s": %s', $page->getId(), $e->getMessage());
                    $this->builder->getLogger()->debug($message);

                    continue;
                }

                /**
                 * Apply a specific path to pages of a section.
                 *
                 * ie:
                 * sections:
                 * - name: Blog
                 *   path: :section/:year/:month/:day/:title
                 */
                if (is_array($this->config->get('sections'))) {
                    foreach ($this->config->get('sections') as $section) {
                        if (array_key_exists('name', $section)) {
                            /** @var Page $page */
                            if ($page->getSection() == Page::slugify($section['name'])) {
                                if (array_key_exists('path', $section)) {
                                    $path = str_replace(
                                        [
                                            ':year',
                                            ':month',
                                            ':day',
                                            ':section',
                                            ':title',
                                            ':slug',
                                        ],
                                        [
                                            $page->getVariable('date')->format('Y'),
                                            $page->getVariable('date')->format('m'),
                                            $page->getVariable('date')->format('d'),
                                            $page->getSection(),
                                            $page->getSlug(),
                                            $page->getSlug(),
                                        ],
                                        $section['path']
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
                    $this->builder->getPages()->remove($page->getId());
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
     * @param Page   $page
     * @param string $format
     *
     * @throws Exception
     *
     * @return Page
     */
    public function convertPage(Page $page, $format = 'yaml'): Page
    {
        // converts frontmatter
        if ($page->getFrontmatter()) {
            try {
                $variables = Converter::convertFrontmatter($page->getFrontmatter(), $format);
            } catch (\Exception $e) {
                throw new Exception($e->getMessage());
            }
            $page->setVariables($variables);
        }

        // converts body
        $html = Converter::convertBody($page->getBody(), $this->builder);
        $page->setBodyHtml($html);

        return $page;
    }
}
