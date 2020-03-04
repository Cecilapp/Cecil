<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Step;

use Cecil\Exception\Exception;
use Symfony\Component\Finder\Finder;

/**
 * Locates content.
 */
class ContentLoad extends AbstractStep
{
    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function init($options)
    {
        if (!is_dir($this->builder->getConfig()->getContentPath())) {
            throw new Exception(sprintf('%s not found!', $this->builder->getConfig()->getContentPath()));
        }

        $this->process = true;
    }

    /**
     * {@inheritdoc}
     */
    public function process()
    {
        call_user_func_array($this->builder->getMessageCb(), ['LOCATE', 'Loading content']);

        $content = Finder::create()
            ->files()
            ->in($this->builder->getConfig()->getContentPath())
            ->name('/\.('.implode('|', (array) $this->builder->getConfig()->get('content.ext')).')$/')
            ->sortByName(true);
        if (!$content instanceof Finder) {
            throw new Exception(sprintf("'%s->%s()' result must be an instance of 'Finder'.", __CLASS__, __FUNCTION__));
        }
        $this->builder->setContent($content);

        $count = $content->count();
        if ($count === 0) {
            call_user_func_array($this->builder->getMessageCb(), ['LOCATE_PROGRESS', 'Nothing to load']);
            return 0;
        }
        call_user_func_array($this->builder->getMessageCb(), ['LOCATE_PROGRESS', 'Files loaded', $count, $count]);
    }
}
