<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Generator;

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
    public function generate(): void
    {
        $subPages = $this->pagesCollection->filter(function (Page $page) {
            return $page->getType() == TYPE::PAGE
                && $page->getId() != 'index'; // exclude homepage
        });
        $pages = $subPages->sortByDate();

        $page = (new Page('index'))->setPath('')->setVariable('title', 'Home');

        if ($this->pagesCollection->has('index')) {
            $page = clone $this->pagesCollection->get('index');
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
        $this->generatedPages->add($page);
    }
}
