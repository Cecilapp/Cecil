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
use Cecil\Exception\Exception;

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
        $virtualpages = $this->config->get('virtualpages');

        // DEBUG
        //var_dump($virtualpages);
        //die();

        foreach ($virtualpages as $frontmatter) {
            if (isset($frontmatter['published']) && $frontmatter['published'] === false) {
                continue;
            }
            if (!array_key_exists('path', $frontmatter)) {
                throw new Exception('Each pages in "virtualpages" config\'s section must have a "path".');
            }
            $page = (new Page(Page::slugify($frontmatter['path'])))
                ->setPath(Page::slugify($frontmatter['path']))
                ->setType(Type::PAGE);
            $page->setVariables($frontmatter);
            $this->generatedPages->add($page);
        }
    }
}
