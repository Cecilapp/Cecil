<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Step;

use Cecil\Collection\Page\Collection as PagesCollection;
use Cecil\Collection\Page\Page;

/**
 * Create Pages collection from content iterator.
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
        call_user_func_array($this->builder->getMessageCb(), ['CREATE', 'Creating pages']);
        $max = count($this->builder->getContent());
        $count = 0;
        /* @var $file \Symfony\Component\Finder\SplFileInfo */
        foreach ($this->builder->getContent() as $file) {
            $count++;
            /* @var $page Page */
            $page = new Page(Page::createId($file));
            $page->setFile($file)->parse();
            $this->builder->getPages()->add($page);
            $message = $page->getId();
            call_user_func_array($this->builder->getMessageCb(), ['CREATE_PROGRESS', $message, $count, $max]);
        }
    }
}
