<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Generator;

use Cecil\Collection\Collection as PageCollection;
use Cecil\Collection\Page\Page;

/**
 * Class PagesFromConfig.
 */
class PagesFromConfig extends AbstractGenerator implements GeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate(PageCollection $pageCollection, \Closure $messageCallback)
    {
        $generatedPages = new PageCollection();

        $fmPages = $this->config->get('site.fmpages');
        foreach ($fmPages as $file => $frontmatter) {
            $page = (new Page())
                ->setId(Page::urlize(sprintf('%s', $file)))
                ->setPathname(Page::urlize(sprintf('%s', $file)));
            $page->setVariables($frontmatter);
            if (!empty($frontmatter['layout'])) {
                $page->setLayout($frontmatter['layout']);
            }
            if (!empty($frontmatter['permalink'])) {
                $page->setPermalink($frontmatter['permalink']);
            }
            $generatedPages->add($page);
        }

        return $generatedPages;
    }
}
