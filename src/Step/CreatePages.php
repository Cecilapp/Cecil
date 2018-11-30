<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Step;

use PHPoole\Collection\Collection as PageCollection;
use PHPoole\Collection\Page\Page;

/**
 * Create Pages collection from content iterator.
 */
class CreatePages extends AbstractStep
{
    /**
     * {@inheritdoc}
     */
    public function process()
    {
        $this->phpoole->setPages(new PageCollection());
        if (count($this->phpoole->getContent()) <= 0) {
            return;
        }
        call_user_func_array($this->phpoole->getMessageCb(), ['CREATE', 'Creating pages']);
        $max = count($this->phpoole->getContent());
        $count = 0;
        /* @var $file \Symfony\Component\Finder\SplFileInfo */
        foreach ($this->phpoole->getContent() as $file) {
            $count++;
            /* @var $page Page */
            $page = (new Page($file))->parse();
            $this->phpoole->getPages()->add($page);
            $message = $page->getPathname();
            call_user_func_array($this->phpoole->getMessageCb(), ['CREATE_PROGRESS', $message, $count, $max]);
        }
    }
}
