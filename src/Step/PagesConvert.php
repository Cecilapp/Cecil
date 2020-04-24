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
