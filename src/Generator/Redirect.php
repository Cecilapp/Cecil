<?php

/**
 * This file is part of Cecil.
 *
 * (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cecil\Generator;

use Cecil\Collection\Page\Page;

/**
 * Redirect generator class.
 *
 * This class is responsible for generating redirect pages based on the
 * 'redirect' variable set in the pages. It filters the pages to find those
 * that have a 'redirect' variable defined and sets their 'layout' to 'redirect'.
 * Pages are updated in place (not cloned) so that references held by other
 * pages (e.g.: section's pages) remain the instances that will be rendered.
 */
class Redirect extends AbstractGenerator implements GeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function generate(): void
    {
        $filteredPages = $this->builder->getPages()->filter(function (Page $page) {
            return $page->getVariable('redirect') !== null
                && $page->getVariable('layout') != 'redirect';
        });

        /** @var Page $page */
        foreach ($filteredPages as $page) {
            $page->setVariable('layout', 'redirect');
            $this->generatedPages->add($page);
        }
    }
}
