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
    /** @var string */
    protected $page;

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
        $this->page = $options['page'];
    }

    /**
     * {@inheritdoc}
     */
    public function process()
    {
        $namePattern = '/\.('.implode('|', (array) $this->builder->getConfig()->get('content.ext')).')$/';
        $content = Finder::create()
            ->files()
            ->in($this->builder->getConfig()->getContentPath())
            ->sortByName(true);
        if ($this->page) {
            if (!util\File::getFS()->exists(Util::joinFile($this->builder->getConfig()->getContentPath(), $this->page))) {
                $this->builder->getLogger()->error(sprintf('File "%s" doesn\'t exist.', $this->page));
            }
            $content->path('.')->path(dirname($this->page));
            $content->name('/index\.('.implode('|', (array) $this->builder->getConfig()->get('content.ext')).')$/');
            $namePattern = basename($this->page);
        }
        $content->name($namePattern);
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
