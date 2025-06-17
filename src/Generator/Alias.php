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
 * Alias generator.
 *
 * This generator creates alias pages for existing pages based on the 'alias' or 'aliases' variable.
 * Each alias will redirect to the original page.
 */
class Alias extends AbstractGenerator implements GeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate(): void
    {
        /** @var Page $page */
        foreach ($this->builder->getPages() ?? [] as $page) {
            $aliases = $this->getPageAliases($page);

            if (!empty($aliases)) {
                foreach ($aliases as $alias) {
                    /** @var Page $aliasPage */
                    $pageId = $path = Page::slugify($alias);
                    // i18n
                    if ($page->getVariable('language') != $this->config->getLanguageDefault()) {
                        $pageId = \sprintf('%s/%s', $page->getVariable('language'), $pageId);
                    }
                    $aliasPage = (new Page($pageId))
                        ->setPath($path)
                        ->setVariables([
                            'layout'   => 'redirect',
                            'redirect' => $page,
                            'title'    => $alias,
                            'date'     => $page->getVariable('date'),
                            'language' => $page->getVariable('language'),
                        ]);
                    $this->generatedPages->add($aliasPage);
                }
            }
        }
    }

    /**
     * Returns aliases array.
     */
    protected function getPageAliases(Page $page): array
    {
        $aliases = [];

        if ($page->hasVariable('alias')) {
            $aliases = $page->getVariable('alias');
        }
        if ($page->hasVariable('aliases')) { // backward compatibility
            $aliases = $page->getVariable('aliases');
        }
        if (!\is_array($aliases)) {
            $aliases = [$aliases];
        }

        return $aliases;
    }
}
