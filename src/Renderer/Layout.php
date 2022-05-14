<?php

/*
 * This file is part of Cecil.
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
use Cecil\Exception\RuntimeException;
use Cecil\Util;

/**
 * Class Layout.
 */
class Layout
{
    const EXT = 'twig';

    /**
     * Layout files finder.
     *
     * @throws RuntimeException
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
            if (Util\File::getFS()->exists(Util::joinFile($config->getLayoutsPath(), $layout))) {
                return [
                    'scope' => 'site',
                    'file'  => $layout,
                ];
            }
            // is it in <theme>/layouts/ dir?
            if ($config->hasTheme()) {
                $themes = $config->getTheme();
                foreach ($themes as $theme) {
                    if (Util\File::getFS()->exists(Util::joinFile($config->getThemeDirPath($theme, 'layouts'), $layout))) {
                        return [
                            'scope' => $theme,
                            'file'  => $layout,
                        ];
                    }
                }
            }
            // is it in resources/layouts/ dir?
            if (Util\File::getFS()->exists(Util::joinPath($config->getInternalLayoutsPath(), $layout))) {
                return [
                    'scope' => 'cecil',
                    'file'  => $layout,
                ];
            }
        }

        throw new RuntimeException(\sprintf('Layout "%s" not found (page: %s).', $layout, $page->getId()));
    }

    /**
     * Layout fall-back.
     *
     * @see finder()
     */
    protected static function fallback(Page $page, string $format): array
    {
        $ext = self::EXT;

        // remove potential redundant extension
        $layout = str_replace(".$ext", '', $page->getVariable('layout'));

        switch ($page->getType()) {
            case PageType::HOMEPAGE:
                $layouts = [
                    // "$layout.$format.$ext",
                    "index.$format.$ext",
                    "list.$format.$ext",
                    "_default/index.$format.$ext",
                    "_default/list.$format.$ext",
                    "_default/page.$format.$ext",
                ];
                if ($page->hasVariable('layout')) {
                    $layouts = array_merge(
                        [sprintf('%s.%s.%s', $layout, $format, $ext)],
                        $layouts
                    );
                }
                break;
            case PageType::SECTION:
                $layouts = [
                    // "$layout.$format.$ext",
                    // "$section/list.$format.$ext",
                    // "section/$section.$format.$ext",
                    "_default/section.$format.$ext",
                    "_default/list.$format.$ext",
                ];
                if ($page->getPath()) {
                    $section = explode('/', $page->getPath())[0];
                    $layouts = array_merge(
                        [sprintf('section/%s.%s.%s', $section, $format, $ext)],
                        $layouts
                    );
                    $layouts = array_merge(
                        [sprintf('%s/list.%s.%s', $section, $format, $ext)],
                        $layouts
                    );
                }
                if ($page->hasVariable('layout')) {
                    $layouts = array_merge(
                        [sprintf('%s.%s.%s', $layout, $format, $ext)],
                        $layouts
                    );
                }
                break;
            case PageType::VOCABULARY:
                $layouts = [
                    // "taxonomy/$plural.$format.$ext", // ie: taxonomy/tags.html.twig
                    "_default/vocabulary.$format.$ext", // ie: _default/vocabulary.html.twig
                ];
                if ($page->hasVariable('plural')) {
                    $layouts = array_merge(
                        [sprintf('taxonomy/%s.%s.%s', $page->getVariable('plural'), $format, $ext)],
                        $layouts
                    );
                }
                break;
            case PageType::TERM:
                $layouts = [
                    // "taxonomy/$term.$format.$ext", // ie: taxonomy/velo.html.twig
                    "_default/term.$format.$ext",     // ie: _default/term.html.twig
                    "_default/list.$format.$ext",     // ie: _default/list.html.twig
                ];
                if ($page->hasVariable('term')) {
                    $layouts = array_merge(
                        [sprintf('taxonomy/%s.%s.%s', $page->getVariable('term'), $format, $ext)],
                        $layouts
                    );
                }
                break;
            default:
                $layouts = [
                    // "$section/$layout.$format.$ext",
                    // "$layout.$format.$ext",
                    // "$section/page.$format.$ext",
                    // "page.$format.$ext",
                    "_default/page.$format.$ext",
                ];
                $layouts = array_merge(
                    ["page.$format.$ext"],
                    $layouts
                );
                if ($page->getSection()) {
                    $layouts = array_merge(
                        [sprintf('%s/page.%s.%s', $page->getSection(), $format, $ext)],
                        $layouts
                    );
                }
                if ($page->hasVariable('layout')) {
                    $layouts = array_merge(
                        [sprintf('%s.%s.%s', $layout, $format, $ext)],
                        $layouts
                    );
                    if ($page->getSection()) {
                        $layouts = array_merge(
                            [sprintf('%s/%s.%s.%s', $page->getSection(), $layout, $format, $ext)],
                            $layouts
                        );
                    }
                }
        }

        return $layouts;
    }
}
