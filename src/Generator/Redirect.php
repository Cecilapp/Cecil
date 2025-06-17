<?php

declare(strict_types=1);

/*
 * This file is part of Cecil.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Generator;

use Cecil\Collection\Page\Page;

/**
 * Redirect generator class.
 *
 * This class is responsible for generating redirect pages based on the
 * 'redirect' variable set in the pages. It filters the pages to find those
 * that have a 'redirect' variable defined and creates a new page with the
 * 'layout' set to 'redirect'. The generated pages are then added to the
 * collection of generated pages.
 */
class Redirect extends AbstractGenerator implements GeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate(): void
    {
        $filteredPages = $this->builder->getPages()->filter(function (Page $page) {
            return $page->getVariable('redirect') !== null
                && $page->getVariable('layout') != 'redirect';
        });

        /** @var Page $page */
        foreach ($filteredPages as $page) {
            $alteredPage = clone $page;
            $alteredPage->setVariable('layout', 'redirect');
            $this->generatedPages->add($alteredPage);
        }
    }
}
