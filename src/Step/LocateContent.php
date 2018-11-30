<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Step;

use PHPoole\Exception\Exception;
use Symfony\Component\Finder\Finder;

/**
 * Locates content.
 */
class LocateContent extends AbstractStep
{
    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function init($options)
    {
        if (!is_dir($this->phpoole->getConfig()->getContentPath())) {
            throw new Exception(sprintf('%s not found!', $this->phpoole->getConfig()->getContentPath()));
        }

        $this->process = true;
    }

    /**
     * {@inheritdoc}
     */
    public function process()
    {
        call_user_func_array($this->phpoole->getMessageCb(), ['LOCATE', 'Loading content']);

        try {
            $content = Finder::create()
                ->files()
                ->in($this->phpoole->getConfig()->getContentPath())
                ->name('/\.('.implode('|', $this->phpoole->getConfig()->get('content.ext')).')$/');
            if (!$content instanceof Finder) {
                throw new Exception(__FUNCTION__.': result must be an instance of Symfony\Component\Finder.');
            }
            $count = $content->count();
            call_user_func_array($this->phpoole->getMessageCb(), ['LOCATE_PROGRESS', 'Start locating', 0, $count]);
            $this->phpoole->setContent($content);
            call_user_func_array($this->phpoole->getMessageCb(), ['LOCATE_PROGRESS', 'Files loaded', $count, $count]);
        } catch (Exception $e) {
            echo $e->getMessage()."\n";
        }
    }
}
