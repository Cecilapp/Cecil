<?php

/**
 * This file is part of Cecil.
 *
 * (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cecil\Renderer;

use Cecil\Collection\Page\Page as CollectionPage;
use Cecil\Collection\Page\Type as PageType;
use Cecil\Exception\RuntimeException;
use Cecil\Util;

/**
 * Layout renderer class.
 *
 * This class is responsible for finding and returning the appropriate layout file
 * for a given page based on its type, section, and other variables.
 * It looks for layout files in various directories such as the site's layouts directory,
 * the theme's layouts directory, and the internal resources/layouts directory.
 */
class Layout
{
    /**
     * Twig template extension.
     * @var string
     */
    public const EXT = 'twig';

    /**
     * Layout files finder.
     *
     * @throws RuntimeException
     */
    public static function finder(CollectionPage $page, string $format, \Cecil\Config $config): array
    {
        $layout = 'unknown';

        // which layouts, in what format, could be used for the page?
        $layouts = self::lookup($page, $format, $config);

        // take the first available layout
        foreach ($layouts as $layout) {
            $layout = Util::joinFile($layout);
            // is it in `layouts/` dir?
            if (Util\File::getFS()->exists(Util::joinFile($config->getLayoutsPath(), $layout))) {
                return [
                    'scope' => 'site',
                    'file'  => $layout,
                ];
            }
            // is it in `<theme>/layouts/` dir?
            if ($config->hasTheme()) {
                $themes = $config->getTheme();
                foreach ($themes ?? [] as $theme) {
                    if (Util\File::getFS()->exists(Util::joinFile($config->getThemeDirPath($theme, 'layouts'), $layout))) {
                        return [
                            'scope' => $theme,
                            'file'  => $layout,
                        ];
                    }
                }
            }
            // is it in resources/layouts/ dir?
            if (Util\File::getFS()->exists(Util::joinPath($config->getLayoutsInternalPath(), $layout))) {
                return [
                    'scope' => 'cecil',
                    'file'  => $layout,
                ];
            }
        }

        throw new RuntimeException(\sprintf('Layout "%s" not found (page: %s).', $layout, $page->getId()));
    }

    /**
     * Templates lookup rules.
     *
     * @see self::finder()
     */
    protected static function lookup(CollectionPage $page, string $format, \Cecil\Config $config): array
    {
        $ext = self::EXT;

        // remove potential redundant extension
        $layout = str_replace(".$ext", '', (string) $page->getVariable('layout'));
        // page section or layout mapping
        $section = $config->getLayoutSection($page->getSection());

        switch ($page->getType()) {
            case PageType::HOMEPAGE->value:
                $layouts = [
                    // "$layout.$format.$ext",
                    "index.$format.$ext",
                    "home.$format.$ext",
                    "list.$format.$ext",
                ];
                if ($page->hasVariable('layout')) {
                    $layouts = array_merge(["$layout.$format.$ext"], $layouts, ["_default/$layout.$format.$ext"]);
                }
                $layouts = array_merge($layouts, [
                    // "_default/$layout.$format.$ext",
                    "_default/index.$format.$ext",
                    "_default/home.$format.$ext",
                    "_default/list.$format.$ext",
                    "_default/page.$format.$ext",
                ]);
                break;
            case PageType::SECTION->value:
                $layouts = [
                    // "$layout.$format.$ext",
                    // "$section/index.$format.$ext",
                    // "$section/list.$format.$ext",
                    // "section/$section.$format.$ext",
                    "_default/section.$format.$ext",
                    "_default/list.$format.$ext",
                ];
                if ($page->getPath()) {
                    $layouts = array_merge(["section/{$section}.$format.$ext"], $layouts);
                    $layouts = array_merge(["{$section}/list.$format.$ext"], $layouts);
                    $layouts = array_merge(["{$section}/index.$format.$ext"], $layouts);
                }
                if ($page->hasVariable('layout')) {
                    $layouts = array_merge(["$layout.$format.$ext"], $layouts);
                }
                break;
            case PageType::VOCABULARY->value:
                $layouts = [
                    // "taxonomy/$plural.$format.$ext", // e.g.: taxonomy/tags.html.twig
                    "_default/vocabulary.$format.$ext", // e.g.: _default/vocabulary.html.twig
                ];
                if ($page->hasVariable('plural')) {
                    $layouts = array_merge(["taxonomy/{$page->getVariable('plural')}.$format.$ext"], $layouts);
                }
                break;
            case PageType::TERM->value:
                $layouts = [
                    // "taxonomy/$term.$format.$ext",     // e.g.: taxonomy/velo.html.twig
                    // "taxonomy/$singular.$format.$ext", // e.g.: taxonomy/tag.html.twig
                    "_default/term.$format.$ext",         // e.g.: _default/term.html.twig
                    "_default/list.$format.$ext",         // e.g.: _default/list.html.twig
                ];
                if ($page->hasVariable('term')) {
                    $layouts = array_merge(["taxonomy/{$page->getVariable('term')}.$format.$ext"], $layouts);
                }
                if ($page->hasVariable('singular')) {
                    $layouts = array_merge(["taxonomy/{$page->getVariable('singular')}.$format.$ext"], $layouts);
                }
                break;
            default:
                $layouts = [
                    // "$section/$layout.$format.$ext",
                    // "$layout.$format.$ext",
                    // "$section/page.$format.$ext",
                    // "_default/$layout.$format.$ext",
                    // "page.$format.$ext",
                    "_default/page.$format.$ext",
                ];
                $layouts = array_merge(["page.$format.$ext"], $layouts);
                if ($page->hasVariable('layout')) {
                    $layouts = array_merge(["_default/$layout.$format.$ext"], $layouts);
                }
                if ($section) {
                    $layouts = array_merge(["{$section}/page.$format.$ext"], $layouts);
                }
                if ($page->hasVariable('layout')) {
                    $layouts = array_merge(["$layout.$format.$ext"], $layouts);
                    if ($section) {
                        $layouts = array_merge(["{$section}/$layout.$format.$ext"], $layouts);
                    }
                }
        }

        // add localized layouts
        if ($page->getVariable('language') !== $config->getLanguageDefault()) {
            foreach ($layouts as $key => $value) {
                $layouts = array_merge(\array_slice($layouts, 0, $key), [str_replace(".$ext", ".{$page->getVariable('language')}.$ext", $value)], \array_slice($layouts, $key));
            }
        }

        return $layouts;
    }
}
