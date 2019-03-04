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
 * Class Homepage.
 */
class Homepage extends AbstractGenerator implements GeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate(PagesCollection $pagesCollection, \Closure $messageCallback)
    {
        $generatedPages = new PagesCollection();

        $subPages = $pagesCollection->filter(function (Page $page) {
            return $page->getType() == TYPE::PAGE;
        });
        $pages = $subPages->sortByDate();

        $page = (new Page('index'))->setPath('')->setVariable('title', 'Home');

        if ($pagesCollection->has('index')) {
            $page = clone $pagesCollection->get('index');
        }
        if ($pages->first()) {
            $page->setVariable('date', $pages->first()->getVariable('date'));
        }
        $page->setType(Type::HOMEPAGE)
            ->setVariables([
                'pages' => $pages,
                'menu'  => [
                    'main' => ['weight' => 1],
                ],
            ]);
        $generatedPages->add($page);

        return $generatedPages;
    }
}
