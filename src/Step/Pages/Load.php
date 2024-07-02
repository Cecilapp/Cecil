<?php

declare(strict_types=1);

/*
 * This file is part of Cecil.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Step\Pages;

use Cecil\Exception\RuntimeException;
use Cecil\Step\AbstractStep;
use Cecil\Util;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Loads pages.
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
        return 'Loading pages';
    }

    /**
     * {@inheritdoc}
     */
    public function init(array $options): void
    {
        if (!is_dir($this->config->getPagesPath())) {
            throw new RuntimeException(sprintf('Pages path "%s" not found.', $this->config->getPagesPath()));
        }

        $this->page = $options['page'];
        $this->canProcess = true;
    }

    /**
     * {@inheritdoc}
     */
    public function process(): void
    {
        $namePattern = '/\.(' . implode('|', (array) $this->config->get('pages.ext')) . ')$/';
        $pages = Finder::create()
            ->files()
            ->in($this->config->getPagesPath())
            ->sort(function (SplFileInfo $a, SplFileInfo $b): int {
                // root pages first
                if (empty($a->getRelativePath()) && !empty($b->getRelativePath())) {
                    return -1;
                }
                if (empty($b->getRelativePath()) && !empty($a->getRelativePath())) {
                    return 1;
                }
                // section's index first
                if ($a->getRelativePath() == $b->getRelativePath() && $a->getBasename('.' . $a->getExtension()) == 'index') {
                    return -1;
                }
                if ($b->getRelativePath() == $a->getRelativePath() && $b->getBasename('.' . $b->getExtension()) == 'index') {
                    return 1;
                }
                // sort by name
                return strnatcasecmp($a->getRelativePath(), $b->getRelativePath());
            });
        // load only one page?
        if ($this->page) {
            // is the page path starts with the `pages.dir` configuration option?
            // (i.e.: `pages/...`, `/pages/...`, `./pages/...`)
            $pagePathAsArray = explode(DIRECTORY_SEPARATOR, Util::joinFile($this->page));
            if ($pagePathAsArray[0] == (string) $this->config->get('pages.dir')) {
                unset($pagePathAsArray[0]);
                $this->page = implode(DIRECTORY_SEPARATOR, $pagePathAsArray);
            }
            if ($pagePathAsArray[0] == '.' && $pagePathAsArray[1] == (string) $this->config->get('pages.dir')) {
                unset($pagePathAsArray[0]);
                unset($pagePathAsArray[1]);
                $this->page = implode(DIRECTORY_SEPARATOR, $pagePathAsArray);
            }
            if (!util\File::getFS()->exists(Util::joinFile($this->config->getPagesPath(), $this->page))) {
                $this->builder->getLogger()->error(sprintf('File "%s" doesn\'t exist.', $this->page));
            }
            $pages->path('.')->path(\dirname($this->page));
            $pages->name('/index\.(' . implode('|', (array) $this->config->get('pages.ext')) . ')$/');
            $namePattern = basename($this->page);
        }
        $pages->name($namePattern);
        if (\is_array($exclude = $this->config->get('pages.exclude'))) {
            $pages->exclude($exclude);
            $pages->notPath($exclude);
            $pages->notName($exclude);
        }
        if (file_exists(Util::joinFile($this->config->getPagesPath(), '.gitignore'))) {
            $pages->ignoreVCSIgnored(true);
        }
        $this->builder->setPagesFiles($pages);

        $total = $pages->count();
        $count = 0;
        if ($total === 0) {
            $this->builder->getLogger()->info('Nothing to load');

            return;
        }
        foreach ($pages as $file) {
            $count++;
            $this->builder->getLogger()->info(sprintf('File "%s" loaded', $file->getRelativePathname()), ['progress' => [$count, $total]]);
        }
    }
}
