<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Step;

use PHPoole\Collection\Page\Page;
use PHPoole\Exception\Exception;
use PHPoole\Util;

/**
 * Pages saving.
 */
class SavePages extends AbstractStep
{
    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function init($options)
    {
        if ($options['dry-run']) {
            $this->process = false;
            call_user_func_array($this->phpoole->getMessageCb(), ['SAVE', 'Dry run']);

            return;
        }

        Util::getFS()->mkdir($this->config->getOutputPath());

        $this->process = true;
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function process()
    {
        call_user_func_array($this->phpoole->getMessageCb(), ['SAVE', 'Saving pages']);

        /* @var $page Page */
        $filteredPages = $this->phpoole->getPages()->filter(function (Page $page) {
            return !empty($page->getVariable('rendered'));
        });
        $max = count($filteredPages);

        $count = 0;
        foreach ($filteredPages as $page) {
            $count++;

            $pathname = $this->cleanPath($this->config->getOutputPath().'/'.$this->getPathname($page));

            Util::getFS()->dumpFile($pathname, $page->getVariable('rendered'));

            $message = substr($pathname, strlen($this->config->getDestinationDir()) + 1);
            call_user_func_array($this->phpoole->getMessageCb(), ['SAVE_PROGRESS', $message, $count, $max]);
        }
    }

    /**
     * Return output pathname.
     *
     * @param Page $page
     *
     * @return string
     */
    protected function getPathname(Page $page)
    {
        // force pathname of a file node page (ie: "section/index.md")
        if ($page->getName() == 'index') {
            return $page->getPath().'/'.$this->config->get('output.filename');
        } else {
            // custom extension, ex: 'manifest.json'
            if (!empty(pathinfo($page->getPermalink(), PATHINFO_EXTENSION))) {
                return $page->getPermalink();
            }
            // underscore prefix, ex: '_redirects'
            if (strpos($page->getPermalink(), '_') === 0) {
                return $page->getPermalink();
            }

            return $page->getPermalink().'/'.$this->config->get('output.filename');
        }
    }

    /**
     * Remove unnecessary slashes.
     *
     * @param string $pathname
     *
     * @return string
     */
    protected function cleanPath($pathname)
    {
        return preg_replace('#/+#', '/', $pathname);
    }
}
