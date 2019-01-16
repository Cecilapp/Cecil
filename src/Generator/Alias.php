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
 * Class Alias.
 */
class Alias extends AbstractGenerator implements GeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate(PagesCollection $pagesCollection, \Closure $messageCallback)
    {
        $generatedPages = new PagesCollection();

        /* @var $page Page */
        foreach ($pagesCollection as $page) {
            $aliases = [];
            if ($page->hasVariable('aliases')) {
                $aliases = $page->getVariable('aliases');
            }
            if ($page->hasVariable('alias')) {
                $aliases = $page->getVariable('alias');
            }
            if (!is_array($aliases)) {
                $aliases = [$aliases];
            }
            if (!empty($aliases)) {
                foreach ($aliases as $alias) {
                    /* @var $aliasPage Page */
                    $pagePathname = Page::urlize($alias);
                    $aliasPage = (new Page())
                        ->setId(sprintf('%s/redirect', $pagePathname))
                        ->setPathname($pagePathname)
                        ->setTitle($alias)
                        ->setLayout('redirect.html')
                        ->setVariable('destination', $page->getPermalink())
                        ->setDate($page->getDate());
                    $generatedPages->add($aliasPage);
                }
            }
        }

        return $generatedPages;
    }
}
