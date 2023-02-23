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

use Cecil\Builder;
use Cecil\Collection\Page\Page;
use Cecil\Converter\Converter;
use Cecil\Converter\ConverterInterface;
use Cecil\Exception\RuntimeException;
use Cecil\Step\AbstractStep;
use Cecil\Util;

/**
 * Converts content of all pages.
 */
class Convert extends AbstractStep
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        if ($this->builder->getBuildOptions()['drafts']) {
            return 'Converting pages (drafts included)';
        }

        return 'Converting pages';
    }

    /**
     * {@inheritdoc}
     */
    public function init(array $options): void
    {
        parent::init($options);

        if (is_dir($this->builder->getConfig()->getPagesPath())) {
            $this->canProcess = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process(): void
    {
        if (count($this->builder->getPages()) == 0) {
            return;
        }

        $total = count($this->builder->getPages());
        $count = 0;
        /** @var Page $page */
        foreach ($this->builder->getPages() as $page) {
            if (!$page->isVirtual()) {
                $count++;

                try {
                    $convertedPage = $this->convertPage($this->builder, $page);
                    // set default language (ex: "en") if necessary
                    if ($convertedPage->getVariable('language') === null) {
                        $convertedPage->setVariable('language', $this->config->getLanguageDefault());
                    }
                } catch (RuntimeException $e) {
                    $this->builder->getLogger()->error(\sprintf('Unable to convert "%s:%s": %s', $e->getPageFile(), $e->getPageLine(), $e->getMessage()));
                    $this->builder->getPages()->remove($page->getId());
                    continue;
                } catch (\Exception $e) {
                    $this->builder->getLogger()->error(\sprintf('Unable to convert "%s": %s', Util::joinPath(Util\File::getFS()->makePathRelative($page->getFilePath(), $this->builder->getConfig()->getPagesPath())), $e->getMessage()));
                    $this->builder->getPages()->remove($page->getId());
                    continue;
                }
                $message = \sprintf('Page "%s" converted', $page->getId());
                // forces drafts convert?
                if ($this->builder->getBuildOptions()['drafts']) {
                    $page->setVariable('published', true);
                }
                if ($page->getVariable('published')) {
                    $this->builder->getPages()->replace($page->getId(), $convertedPage);
                } else {
                    $message .= ' (not published)';
                }
                $this->builder->getLogger()->info($message, ['progress' => [$count, $total]]);
            }
        }
    }

    /**
     * Converts page content:
     *  - front matter to PHP array
     *  - body to HTML.
     *
     * @throws RuntimeException
     */
    public function convertPage(Builder $builder, Page $page, ?string $format = null, ?ConverterInterface $converter = null): Page
    {
        $format = $format ?? $builder->getConfig()->get('frontmatter.format') ?? 'yaml';
        $converter = $converter ?? new Converter($builder);

        // converts front matter
        if ($page->getFrontmatter()) {
            try {
                $variables = $converter->convertFrontmatter($page->getFrontmatter(), $format);
            } catch (RuntimeException $e) {
                throw new RuntimeException($e->getMessage(), $page->getFilePath(), $e->getPageLine());
            }
            $page->setFmVariables($variables);
            $page->setVariables($variables);
        }

        // converts body (only if page is published or drafts option is enabled)
        if ($page->getVariable('published') || $this->options['drafts']) {
            try {
                $html = $converter->convertBody($page->getBody());
            } catch (RuntimeException $e) {
                throw new \Exception($e->getMessage());
            }
            $page->setBodyHtml($html);
        }

        return $page;
    }
}
