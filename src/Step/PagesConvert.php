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
    public function init($options)
    {
        /** @var \Cecil\Builder $builder */
        if (is_dir($this->builder->getConfig()->getContentPath())) {
            $this->process = true;
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
        $message = 'Converting pages';
        if ($this->builder->getBuildOptions()['drafts']) {
            $message .= ' (drafts included)';
        }
        call_user_func_array($this->builder->getMessageCb(), ['CONVERT', $message]);
        $max = count($this->builder->getPages());
        $count = 0;
        $countError = 0;
        /** @var Page $page */
        foreach ($this->builder->getPages() as $page) {
            if (!$page->isVirtual()) {
                $count++;

                try {
                    $convertedPage = $this->convertPage($page, (string) $this->config->get('frontmatter.format'));
                } catch (Exception $e) {
                    $this->builder->getPages()->remove($page->getId());
                    $countError++;

                    $message = sprintf('%s: unable to convert front matter (%s)', $page->getId(), $e->getMessage());
                    call_user_func_array($this->builder->getMessageCb(), ['CONVERT_ERROR', $message]);

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
                call_user_func_array($this->builder->getMessageCb(), ['CONVERT_PROGRESS', $message, $count, $max]);
            }
        }
        if ($countError > 0) {
            $message = sprintf('Number of errors: %s', $countError);
            call_user_func_array($this->builder->getMessageCb(), ['CONVERT_ERROR', $message]);
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
