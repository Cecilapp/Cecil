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
 * Class TitleReplace.
 */
class Redirect extends AbstractGenerator implements GeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate(PagesCollection $pagesCollection, \Closure $messageCallback)
    {
        $generatedPages = new PagesCollection();

        $filteredPages = $pagesCollection->filter(function (Page $page) {
            return null !== $page->getVariable('redirect');
        });

        /* @var $page Page */
        foreach ($filteredPages as $page) {
            $alteredPage = clone $page;
            $alteredPage->setLayout('redirect.html');
            $alteredPage->setVariable('destination', $page->getVariable('redirect'));
            $generatedPages->add($alteredPage);
        }

        return $generatedPages;
    }
}
