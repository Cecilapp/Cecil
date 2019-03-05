<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
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
        /* @var $page Page */
        foreach ($this->builder->getPages() as $page) {
            if (!$page->isVirtual()) {
                $count++;
                $convertedPage = $this->convertPage($page, $this->builder->getConfig()->get('frontmatter.format'));
                if (false !== $convertedPage) {
                    $message = $page->getId();
                    // force convert drafts?
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
                } else {
                    $this->builder->getPages()->remove($page->getId());
                    $countError++;
                }
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
     * @return Page
     */
    public function convertPage(Page $page, $format = 'yaml')
    {
        // converts frontmatter
        if ($page->getFrontmatter()) {
            try {
                $variables = Converter::convertFrontmatter($page->getFrontmatter(), $format);
            } catch (Exception $e) {
                $message = sprintf("Unable to convert frontmatter of '%s': %s", $page->getId(), $e->getMessage());
                call_user_func_array($this->builder->getMessageCb(), ['CONVERT_ERROR', $message]);

                return false;
            }
            $page->setVariables($variables);
        }

        // converts body
        $html = Converter::convertBody($page->getBody());
        $page->setBodyHtml($html);

        return $page;
    }
}
