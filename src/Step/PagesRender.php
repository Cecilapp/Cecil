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
use Cecil\Renderer\Layout;
use Cecil\Renderer\Twig;

/**
 * Pages rendering.
 */
class PagesRender extends AbstractStep
{
    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function init($options)
    {
        if (!is_dir($this->config->getLayoutsPath()) && !$this->config->hasTheme()) {
            throw new Exception(sprintf(
                "'%s' is not a valid layouts directory",
                $this->config->getLayoutsPath()
            ));
        }

        $this->process = true;
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function process()
    {
        // prepares renderer
        $this->builder->setRenderer(new Twig($this->getAllLayoutsPaths(), $this->builder));

        // add globals variables
        $this->addGlobals();

        call_user_func_array($this->builder->getMessageCb(), ['RENDER', 'Rendering pages']);

        // collect published pages
        /* @var $page Page */
        $filteredPages = $this->builder->getPages()->filter(function (Page $page) {
            return !empty($page->getVariable('published'));
        });
        $max = count($filteredPages);

        // render each page
        $count = 0;
        /* @var $page Page */
        foreach ($filteredPages as $page) {
            $count++;
            $formats = ['html'];
            $rendered = null;
            $alternates = [];

            // get page's output formats
            $formats = $this->getFormats($page);
            $page->setVariable('output', $formats);

            // get pages's alternates links
            $alternates = $this->getAlternates($page, $formats);
            $page->setVariable('alternates', $alternates);

            // render each output format
            foreach ($formats as $format) {
                // escape redirect pages
                if ($format != 'html' && $page->hasVariable('destination')) {
                    continue;
                }
                // search for the template
                $layout = Layout::finder($page, $format, $this->config);
                // render with Twig
                $rendered[$format]['output'] = $this->builder->getRenderer()->render(
                    $layout,
                    ['page' => $page]
                );
                $rendered[$format]['template'] = $layout;
            }
            $page->setVariable('rendered', $rendered);
            $this->builder->getPages()->replace($page->getId(), $page);

            $layouts = implode(', ', array_column($rendered, 'template'));
            $message = sprintf('%s (%s)', ($page->getId() ?: 'index'), $layouts);
            call_user_func_array($this->builder->getMessageCb(), ['RENDER_PROGRESS', $message, $count, $max]);
        }
    }

    /**
     * Return an array of layouts directories.
     *
     * @return array
     */
    protected function getAllLayoutsPaths(): array
    {
        $paths = [];

        // layouts/
        if (is_dir($this->config->getLayoutsPath())) {
            $paths[] = $this->config->getLayoutsPath();
        }
        // <theme>/layouts/
        if ($this->config->hasTheme()) {
            $themes = $this->config->getTheme();
            foreach ($themes as $theme) {
                $paths[] = $this->config->getThemeDirPath($theme);
            }
        }
        // res/layouts/
        if (is_dir($this->config->getInternalLayoutsPath())) {
            $paths[] = $this->config->getInternalLayoutsPath();
        }

        return $paths;
    }

    /**
     * Add globals variables.
     */
    protected function addGlobals()
    {
        // adds global variables
        $this->builder->getRenderer()->addGlobal('site', array_merge(
            $this->config->get('site'),
            ['menus' => $this->builder->getMenus()],
            ['pages' => $this->builder->getPages()->filter(function (Page $page) {
                return $page->getVariable('published');
            })],
            ['time' => time()]
        ));
        $this->builder->getRenderer()->addGlobal('cecil', [
            'url'       => sprintf('https://cecil.app/#%s', $this->builder->getVersion()),
            'version'   => $this->builder->getVersion(),
            'poweredby' => sprintf('Cecil v%s', $this->builder->getVersion()),
        ]);
    }

    /**
     * Get available formats.
     *
     * @param Page $page
     *
     * @return array
     */
    protected function getFormats(Page $page): array
    {
        $formats = [];

        // Get page format(s).
        // ie: "output: txt"
        if ($page->getVariable('output')) {
            $formats = $page->getVariable('output');
            if (!\is_array($formats)) {
                $formats = [$formats];
            }
        } else {
            // get available formats for page type
            // ie: "'page' => ['html', 'json']"
            if (\is_array($this->config->get('site.output.pagetypeformats.'.$page->getType()))) {
                $formats = $this->config->get('site.output.pagetypeformats.'.$page->getType());
            }
        }

        return $formats;
    }

    /**
     * Get page's alternates.
     *
     * @param Page  $page
     * @param array $formats
     *
     * @return array
     */
    protected function getAlternates(Page $page, array $formats): array
    {
        $alternates = [];

        if (count($formats) > 1 && in_array('html', $formats)) {
            foreach ($formats as $format) {
                $format == 'html' ? $rel = 'canonical' : $rel = 'alternate';
                $alternates[] = [
                    'rel'    => $rel,
                    'type'   => $this->config->get("site.output.formats.$format.mediatype"),
                    'title'  => strtoupper($format),
                    'format' => $format,
                ];
            }
        }

        return $alternates;
    }
}
