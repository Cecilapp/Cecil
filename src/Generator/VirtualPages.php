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
 * Class VirtualPages.
 */
class VirtualPages extends AbstractGenerator implements GeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate(): void
    {
        $virtualpages = $this->config->get('site.virtualpages');
        foreach ($virtualpages as $path => $frontmatter) {
            if (isset($frontmatter['published']) && $frontmatter['published'] === false) {
                continue;
            }
            $page = (new Page(Page::slugify($path)))
                ->setPath(Page::slugify($path))
                ->setType(Type::PAGE);
            $page->setVariables($frontmatter);
            $this->generatedPages->add($page);
        }
    }
}
