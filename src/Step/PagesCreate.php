<?php
/**
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Step;

use Cecil\Collection\Page\Collection as PagesCollection;
use Cecil\Collection\Page\Page;

/**
 * Creates Pages collection from content iterator.
 */
class PagesCreate extends AbstractStep
{
    /**
     * {@inheritdoc}
     */
    public function init($options)
    {
        /** @var \Cecil\Builder $builder */
        $this->builder->setPages(new PagesCollection('all-pages'));

        if (is_dir($this->builder->getConfig()->getContentPath())) {
            $this->process = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process()
    {
        if (count($this->builder->getContent()) <= 0) {
            return;
        }

        $this->builder->getLogger()->notice('Creating pages');

        $max = count($this->builder->getContent());
        $count = 0;
        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach ($this->builder->getContent() as $file) {
            $count++;
            /** @var Page $page */
            $page = new Page(Page::createId($file));
            $page->setFile($file)->parse();
            $this->builder->getPages()->add($page);

            $message = $page->getId();
            $this->builder->getLogger()->info($message, ['progress' => [$count, $max]]);
        }
    }
}
