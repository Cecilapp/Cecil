<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Step;

use Cecil\Collection\Page\Page;
use Cecil\Exception\Exception;
use Cecil\Util;

/**
 * Pages saving.
 */
class PagesSave extends AbstractStep
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
            call_user_func_array($this->builder->getMessageCb(), ['SAVE', 'Dry run']);

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
        call_user_func_array($this->builder->getMessageCb(), ['SAVE', 'Saving pages']);

        /* @var $page Page */
        $filteredPages = $this->builder->getPages()->filter(function (Page $page) {
            return !empty($page->getVariable('rendered'));
        });
        $max = count($filteredPages);

        $count = 0;
        foreach ($filteredPages as $page) {
            $count++;
            $message = [];

            foreach ($page->getVariable('rendered') as $format => $rendered) {
                if (false === $pathname = $this->getPathname($page, $format)) {
                    throw new Exception(sprintf(
                        "Can't get pathname of page '%s' (format: '%s')",
                        $page->getId(),
                        $format
                    ));
                    continue;
                }
                $pathname = $this->cleanPath($this->config->getOutputPath().'/'.$pathname);
                Util::getFS()->dumpFile($pathname, $rendered['output']);

                $message[] = substr($pathname, strlen($this->config->getDestinationDir()) + 1);
            }

            call_user_func_array(
                $this->builder->getMessageCb(),
                ['SAVE_PROGRESS', implode(', ', $message), $count, $max]
            );
        }
    }

    /**
     * Return output pathname.
     *
     * @param Page   $page
     * @param string $format
     *
     * @return string|false
     */
    protected function getPathname(Page $page, string $format = 'html')
    {
        // force pathname of "index" pages (ie: homepage, sections, etc.)
        if ($page->getName() == 'index') {
            return $page->getPath().'/'.$this->config->get("site.output.formats.$format.filename");
        }
        // if "pathname/"
        if (\strrpos($page->getVariable('url'), '/') == \strlen($page->getVariable('url')) - 1) {
            return $page->getVariable('url').$this->config->get("site.output.formats.$format.filename");
        }
        // != html + extension. ie: 404.html -> 404.json
        $extension = pathinfo($page->getVariable('url'), PATHINFO_EXTENSION);
        if ($format != 'html' && $extension != null) {
            $url = basename($page->getVariable('url'), ".$extension");

            return $url.'/'.$this->config->get("site.output.formats.$format.filename");
        }
        // if "pathname"
        return $page->getVariable('url');
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
