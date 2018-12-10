<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Renderer;

use Cecil\Collection\Page\Page;
use Cecil\Config;
use Cecil\Exception\Exception;
use Cecil\Page\NodeType;
use Cecil\Util;

/**
 * Class Layout.
 */
class Layout
{
    /**
     * Layout file finder.
     *
     * @param Page   $page
     * @param Config $config
     *
     * @throws Exception
     *
     * @return string
     */
    public function finder(Page $page, Config $config)
    {
        $layout = 'unknown';

        // what layouts could be use for the page?
        $layouts = self::fallback($page);

        // take the first available layout
        foreach ($layouts as $layout) {
            // is it in layouts/ dir?
            if (Util::getFS()->exists($config->getLayoutsPath().'/'.$layout)) {
                return $layout;
            }
            // is it in <theme>/layouts/ dir?
            if ($config->hasTheme()) {
                $themes = $config->getTheme();
                foreach ($themes as $theme) {
                    if (Util::getFS()->exists($config->getThemeDirPath($theme, 'layouts').'/'.$layout)) {
                        return $layout;
                    }
                }
            }
            // is it in res/layouts/ dir?
            if (Util::getFS()->exists($config->getInternalLayoutsPath().'/'.$layout)) {
                return $layout;
            }
        }

        throw new Exception(sprintf("Layout '%s' not found for page '%s'!", $layout, $page->getId()));
    }

    /**
     * Layout fall-back.
     *
     * @param $page
     *
     * @return string[]
     *
     * @see finder()
     */
    protected static function fallback(Page $page)
    {
        // remove redundant '.twig' extension
        $layout = str_replace('.twig', '', $page->getLayout());

        switch ($page->getNodeType()) {
            case NodeType::HOMEPAGE:
                $layouts = [
                    'index.html.twig',
                    '_default/list.html.twig',
                    '_default/page.html.twig',
                ];
                break;
            case NodeType::SECTION:
                $layouts = [
                    // 'section/$layout.twig',
                    // 'section/$section.html.twig',
                    '_default/section.html.twig',
                    '_default/list.html.twig',
                ];
                if ($page->getPathname()) {
                    $section = explode('/', $page->getPathname())[0];
                    $layouts = array_merge(
                        [sprintf('section/%s.html.twig', $section)],
                        $layouts
                    );
                }
                if ($page->getLayout()) {
                    $layouts = array_merge(
                        [sprintf('section/%s.twig', $layout)],
                        $layouts
                    );
                }
                break;
            case NodeType::TAXONOMY:
                $layouts = [
                    // 'taxonomy/$singular.html.twig',
                    '_default/taxonomy.html.twig',
                    '_default/list.html.twig',
                ];
                if ($page->getVariable('singular')) {
                    $layouts = array_merge(
                        [sprintf('taxonomy/%s.html.twig', $page->getVariable('singular'))],
                        $layouts
                    );
                }
                break;
            case NodeType::TERMS:
                $layouts = [
                    // 'taxonomy/$singular.terms.html.twig',
                    '_default/terms.html.twig',
                ];
                if ($page->getVariable('singular')) {
                    $layouts = array_merge(
                        [sprintf('taxonomy/%s.terms.html.twig', $page->getVariable('singular'))],
                        $layouts
                    );
                }
                break;
            default:
                $layouts = [
                    // '$section/$layout.twig',
                    // '$layout.twig',
                    // '$section/page.html.twig',
                    // 'page.html.twig',
                    '_default/page.html.twig',
                ];
                $layouts = array_merge(
                    ['page.html.twig'],
                    $layouts
                );

                if ($page->getSection()) {
                    $layouts = array_merge(
                        [sprintf('%s/page.html.twig', $page->getSection())],
                        $layouts
                    );
                }
                if ($page->getLayout()) {
                    $layouts = array_merge(
                        [sprintf('%s.twig', $layout)],
                        $layouts
                    );
                    if ($page->getSection()) {
                        $layouts = array_merge(
                            [sprintf('%s/%s.twig', $page->getSection(), $layout)],
                            $layouts
                        );
                    }
                }
        }

        return $layouts;
    }
}
