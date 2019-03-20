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
use Cecil\Collection\Page\Type;

/**
 * Class Section.
 */
class Section extends AbstractGenerator implements GeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate(): void
    {
        $sections = [];

        // identify sections
        /* @var $page Page */
        foreach ($this->pagesCollection as $page) {
            if ($page->getSection()) {
                $sections[$page->getSection()][] = $page;
            }
        }

        // adds section to pages collection
        if (count($sections) > 0) {
            $menuWeight = 100;
            foreach ($sections as $sectionName => $pagesArray) {
                $pageId = $path = Page::slugify($sectionName);
                $page = (new Page($pageId))->setVariable('title', ucfirst($sectionName));
                if ($this->pagesCollection->has($pageId)) {
                    $page = clone $this->pagesCollection->get($pageId);
                }
                $pages = (new PagesCollection($sectionName, $pagesArray))->sortByDate();
                $page->setPath($path)
                    ->setType(Type::SECTION)
                    ->setVariable('pages', $pages)
                    ->setVariable('date', $pages->first()->getVariable('date'))
                    ->setVariable('menu', [
                        'main' => ['weight' => $menuWeight],
                    ]);
                $this->generatedPages->add($page);
                $menuWeight += 10;
            }
        }
    }
}
