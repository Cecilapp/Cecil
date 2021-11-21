<?php
/**
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Step\Content;

use Cecil\Step\AbstractStep;
use Cecil\Util;
use Symfony\Component\Finder\Finder;

/**
 * Loads content.
 */
class Load extends AbstractStep
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Loading content';
    }

    /**
     * {@inheritdoc}
     */
    public function init($options)
    {
        /** @var \Cecil\Builder $builder */
        if (is_dir($this->builder->getConfig()->getContentPath())) {
            $this->canProcess = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process()
    {
        $content = Finder::create()
            ->files()
            ->in($this->builder->getConfig()->getContentPath())
            ->name('/\.('.implode('|', (array) $this->builder->getConfig()->get('content.ext')).')$/')
            ->sortByName(true);
        if (file_exists(Util::joinFile($this->builder->getConfig()->getContentPath(), '.gitignore'))) {
            $content->ignoreVCSIgnored(true);
        }
        $this->builder->setContent($content);

        $count = $content->count();
        if ($count === 0) {
            $this->builder->getLogger()->info('Nothing to load');

            return 0;
        }
        $this->builder->getLogger()->info('Files loaded', ['progress' => [$count, $count]]);
    }
}
