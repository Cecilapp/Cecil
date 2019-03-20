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
 * Class Alias.
 */
class Alias extends AbstractGenerator implements GeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate(): void
    {
        /* @var $page Page */
        foreach ($this->pagesCollection as $page) {
            $aliases = $this->getPageAliases($page);

            if (!empty($aliases)) {
                foreach ($aliases as $alias) {
                    /* @var $aliasPage Page */
                    $pageId = $path = Page::slugify($alias);
                    $aliasPage = (new Page($pageId))
                        ->setPath($path)
                        ->setVariables([
                            'layout'   => 'redirect',
                            'redirect' => $page->getPath(),
                            'title'    => $alias,
                            'date'     => $page->getVariable('date'),
                        ]);
                    $this->generatedPages->add($aliasPage);
                }
            }
        }
    }

    /**
     * Return aliases array.
     *
     * @param Page $page
     *
     * @return array
     */
    protected function getPageAliases(Page $page): array
    {
        $aliases = [];

        if ($page->hasVariable('aliases')) { // backward compatibility
            $aliases = $page->getVariable('aliases');
        }
        if ($page->hasVariable('alias')) {
            $aliases = $page->getVariable('alias');
        }
        if (!is_array($aliases)) {
            $aliases = [$aliases];
        }

        return $aliases;
    }
}
