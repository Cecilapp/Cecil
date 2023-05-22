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
        // legacy support
        if (is_dir(Util::joinFile($this->config->getSourceDir(), 'content'))) {
            $this->builder->getLogger()->alert('"content" directory is deprecated, please rename it to "pages"');
        }

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
        $content = Finder::create()
            ->files()
            ->in($this->config->getPagesPath())
            ->sortByName(true);
        // load only one page?
        if ($this->page) {
            // is the page path starts with the `pages.dir` configuration option?
            // (i.e.: `pages/...`, `/pages/...`, `./pages/...`)
            $pagePathAsArray = explode(DIRECTORY_SEPARATOR, $this->page);
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
            $content->path('.')->path(\dirname($this->page));
            $content->name('/index\.(' . implode('|', (array) $this->config->get('pages.ext')) . ')$/');
            $namePattern = basename($this->page);
        }
        $content->name($namePattern);
        if (\is_array($this->config->get('pages.exclude'))) {
            $content->exclude($this->config->get('pages.exclude'));
            $content->notPath($this->config->get('pages.exclude'));
            $content->notName($this->config->get('pages.exclude'));
        }
        if (file_exists(Util::joinFile($this->config->getPagesPath(), '.gitignore'))) {
            $content->ignoreVCSIgnored(true);
        }
        $this->builder->setPagesFiles($content);

        $count = $content->count();
        if ($count === 0) {
            $this->builder->getLogger()->info('Nothing to load');

            return;
        }
        $this->builder->getLogger()->info('Files loaded', ['progress' => [$count, $count]]);
    }
}
