<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Generator;

use Cecil\Collection\Page\Page;

/**
 * Class TitleReplace.
 */
class TitleReplace extends AbstractGenerator implements GeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate(): void
    {
        $filteredPages = $this->pagesCollection->filter(function (Page $page) {
            return null !== $page->getVariable('title');
        });

        /** @var Page $page */
        foreach ($filteredPages as $page) {
            $alteredPage = clone $page;
            $alteredPage->setVariable('title', ucwords($page->getVariable('title')));
            $this->generatedPages->add($alteredPage);
        }
    }
}
