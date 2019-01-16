<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Generator;

use Cecil\Collection\Page\Collection as PagesCollection;
use Cecil\Collection\Page\Page;

/**
 * Class PagesFromConfig.
 */
class PagesFromConfig extends AbstractGenerator implements GeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate(PagesCollection $pagesCollection, \Closure $messageCallback)
    {
        $generatedPages = new PagesCollection();

        $fmPages = $this->config->get('site.fmpages');
        foreach ($fmPages as $file => $frontmatter) {
            $pageId = Page::urlize(sprintf('%s', $file));
            $page = (new Page())
                ->setId($pageId)
                ->setPathname($pageId);
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
