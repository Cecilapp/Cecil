<?php
/**
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Step\Pages;

use Cecil\Collection\Page\Collection as PagesCollection;
use Cecil\Collection\Page\Page;
use Cecil\Step\AbstractStep;

/**
 * Creates Pages collection from content iterator.
 */
class Create extends AbstractStep
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Creating pages';
    }

    /**
     * {@inheritdoc}
     */
    public function init($options)
    {
        /** @var \Cecil\Builder $builder */
        $this->builder->setPages(new PagesCollection('all-pages'));

        if (is_dir($this->builder->getConfig()->getContentPath())) {
            $this->canProcess = true;
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

        $max = count($this->builder->getContent());
        $count = 0;
        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach ($this->builder->getContent() as $file) {
            $count++;
            /** @var Page $page */
            $page = new Page(Page::createId($file));
            $page->setFile($file)->parse();
            if (in_array($page->getVariable('language') ?? $this->config->getLanguageDefault(), array_column($this->config->getLanguages(), 'code'))) {
                $this->builder->getPages()->add($page);
            }

            $message = sprintf('Page "%s" created', $page->getId());
            $this->builder->getLogger()->info($message, ['progress' => [$count, $max]]);
        }
    }
}
