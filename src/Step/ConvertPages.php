<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Step;

use PHPoole\Collection\Page\Page;
use PHPoole\Converter\Converter;
use PHPoole\Exception\Exception;

/**
 * Converts content of all pages.
 */
class ConvertPages extends AbstractStep
{
    /**
     * {@inheritdoc}
     */
    public function process()
    {
        if (count($this->phpoole->getPages()) <= 0) {
            return;
        }
        $message = 'Converting pages';
        if ($this->phpoole->getBuildOptions()['drafts']) {
            $message .= ' (drafts included)';
        }
        call_user_func_array($this->phpoole->getMessageCb(), ['CONVERT', $message]);
        $max = count($this->phpoole->getPages());
        $count = 0;
        $countError = 0;
        /* @var $page Page */
        foreach ($this->phpoole->getPages() as $page) {
            if (!$page->isVirtual()) {
                $count++;
                $convertedPage = $this->convertPage($page, $this->phpoole->getConfig()->get('frontmatter.format'));
                if (false !== $convertedPage) {
                    $message = $page->getPathname();
                    // force convert drafts?
                    if ($this->phpoole->getBuildOptions()['drafts']) {
                        $page->setVariable('published', true);
                    }
                    if ($page->getVariable('published')) {
                        $this->phpoole->getPages()->replace($page->getId(), $convertedPage);
                    } else {
                        $this->phpoole->getPages()->remove($page->getId());
                        $message .= ' (not published)';
                    }
                    call_user_func_array($this->phpoole->getMessageCb(), ['CONVERT_PROGRESS', $message, $count, $max]);
                } else {
                    $this->phpoole->getPages()->remove($page->getId());
                    $countError++;
                }
            }
        }
        if ($countError > 0) {
            $message = sprintf('Number of errors: %s', $countError);
            call_user_func_array($this->phpoole->getMessageCb(), ['CONVERT_ERROR', $message]);
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
                call_user_func_array($this->phpoole->getMessageCb(), ['CONVERT_ERROR', $message]);

                return false;
            }
            $page->setVariables($variables);
        }

        // converts body
        $html = Converter::convertBody($page->getBody());
        $page->setHtml($html);

        return $page;
    }
}
