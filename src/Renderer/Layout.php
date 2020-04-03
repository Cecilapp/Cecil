<?php
/**
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Renderer;

use Cecil\Collection\Page\Page;
use Cecil\Collection\Page\Type as PageType;
use Cecil\Config;
use Cecil\Exception\Exception;
use Cecil\Util;

/**
 * Class Layout.
 */
class Layout
{
    /**
     * Layout files finder.
     *
     * @param Page   $page
     * @param string $format
     * @param Config $config
     *
     * @throws Exception
     *
     * @return array
     */
    public static function finder(Page $page, string $format, Config $config): array
    {
        $layout = 'unknown';

        // what layouts, in what format, could be use for the page?
        $layouts = self::fallback($page, $format);

        // take the first available layout
        foreach ($layouts as $layout) {
            $layout = Util::joinFile($layout);
            // is it in layouts/ dir?
            if (Util::getFS()->exists(Util::joinFile($config->getLayoutsPath(), $layout))) {
                return [
                    'scope' => 'site',
                    'file'  => $layout,
                ];
            }
            // is it in <theme>/layouts/ dir?
            if ($config->hasTheme()) {
                $themes = $config->getTheme();
                foreach ($themes as $theme) {
                    if (Util::getFS()->exists(Util::joinFile($config->getThemeDirPath($theme, 'layouts'), $layout))) {
                        return [
                            'scope' => $theme,
                            'file'  => $layout,
                        ];
                    }
                }
            }
            // is it in res/layouts/ dir?
            if (Util::getFS()->exists(Util::joinPath($config->getInternalLayoutsPath(), $layout))) {
                return [
                    'scope' => 'cecil',
                    'file'  => $layout,
                ];
            }
        }

        throw new Exception(sprintf("Layout '%s' not found for page '%s'!", $layout, $page->getId()));
    }

    /**
     * Layout fall-back.
     *
     * @param Page   $page
     * @param string $format
     *
     * @return string[]
     *
     * @see finder()
     */
    protected static function fallback(Page $page, string $format): array
    {
        // remove redundant '.twig' extension
        $layout = str_replace('.twig', '', $page->getVariable('layout'));

        switch ($page->getType()) {
            case PageType::HOMEPAGE:
                // "$layout.$format.twig",
                $layouts = [
                    "index.$format.twig",
                    "_default/list.$format.twig",
                    "_default/page.$format.twig",
                ];
                if ($page->getVariable('layout')) {
                    $layouts = array_merge(
                        [sprintf('%s.%s.twig', $layout, $format)],
                        $layouts
                    );
                }
                break;
            case PageType::SECTION:
                $layouts = [
                    // "section/$section.$format.twig",
                    "_default/section.$format.twig",
                    "_default/list.$format.twig",
                ];
                if ($page->getPath()) {
                    $section = explode('/', $page->getPath())[0];
                    $layouts = array_merge(
                        [sprintf('section/%s.%s.twig', $section, $format)],
                        $layouts
                    );
                }
                break;
            case PageType::VOCABULARY:
                $layouts = [
                    // "taxonomy/$plural.$format.twig", // ie: taxonomy/tags.html.twig
                    "_default/vocabulary.$format.twig", // ie: _default/vocabulary.html.twig
                ];
                if ($page->getVariable('plural')) {
                    $layouts = array_merge(
                        [sprintf('taxonomy/%s.%s.twig', $page->getVariable('plural'), $format)],
                        $layouts
                    );
                }
                break;
            case PageType::TERM:
                $layouts = [
                    // "taxonomy/$term.$format.twig", // ie: taxonomy/velo.html.twig
                    "_default/term.$format.twig",     // ie: _default/term.html.twig
                    "_default/list.$format.twig",     // ie: _default/list.html.twig
                ];
                if ($page->getVariable('term')) {
                    $layouts = array_merge(
                        [sprintf('taxonomy/%s.%s.twig', $page->getVariable('term'), $format)],
                        $layouts
                    );
                }
                break;
            default:
                $layouts = [
                    // "$section/$layout.$format.twig",
                    // "$layout.$format.twig",
                    // "$section/page.$format.twig",
                    // "page.$format.twig",
                    "_default/page.$format.twig",
                ];
                $layouts = array_merge(
                    ["page.$format.twig"],
                    $layouts
                );
                if ($page->getSection()) {
                    $layouts = array_merge(
                        [sprintf('%s/page.%s.twig', $page->getSection(), $format)],
                        $layouts
                    );
                }
                if ($page->getVariable('layout')) {
                    $layouts = array_merge(
                        [sprintf('%s.%s.twig', $layout, $format)],
                        $layouts
                    );
                    if ($page->getSection()) {
                        $layouts = array_merge(
                            [sprintf('%s/%s.%s.twig', $page->getSection(), $layout, $format)],
                            $layouts
                        );
                    }
                }
        }

        return $layouts;
    }
}
