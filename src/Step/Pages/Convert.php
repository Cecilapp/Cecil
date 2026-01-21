<?php

/**
 * This file is part of Cecil.
 *
 * (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cecil\Step\Pages;

use Cecil\Builder;
use Cecil\Collection\Page\Page;
use Cecil\Config;
use Cecil\Converter\Converter;
use Cecil\Converter\ConverterInterface;
use Cecil\Exception\RuntimeException;
use Cecil\Step\AbstractStep;
use Cecil\Util;
use DI\Attribute\Inject;
use Psr\Log\LoggerInterface;

/**
 * Convert step.
 *
 * This step is responsible for converting pages from their source format
 * (i.e. Markdown) to HTML, applying front matter processing,
 * and ensuring that the pages are ready for rendering. It handles both
 * published and draft pages, depending on the build options.
 */
class Convert extends AbstractStep
{
    #[Inject]
    private Converter $converter;

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

        if (\is_null($this->builder->getPages())) {
            $this->canProcess = false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process(): void
    {
        if (!is_iterable($this->builder->getPages()) || \count($this->builder->getPages()) == 0) {
            return;
        }

        $total = \count($this->builder->getPages());
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
                    $this->logger->error(\sprintf('Unable to convert "%s:%s": %s', $e->getFile(), $e->getLine(), $e->getMessage()));
                    $this->builder->getPages()->remove($page->getId());
                    continue;
                } catch (\Exception $e) {
                    $this->logger->error(\sprintf('Unable to convert "%s": %s', Util::joinPath(Util\File::getFS()->makePathRelative($page->getFilePath(), $this->config->getPagesPath())), $e->getMessage()));
                    $this->builder->getPages()->remove($page->getId());
                    continue;
                }
                $message = \sprintf('Page "%s" converted', $page->getId());
                $statusMessage = ' (not published)';
                // forces drafts convert?
                if ($this->builder->getBuildOptions()['drafts']) {
                    $page->setVariable('published', true);
                }
                // replaces page in collection
                if ($page->getVariable('published')) {
                    $this->builder->getPages()->replace($page->getId(), $convertedPage);
                    $statusMessage = '';
                }
                $this->logger->info($message . $statusMessage, ['progress' => [$count, $total]]);
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
        $format = $format ?? (string) $builder->getConfig()->get('pages.frontmatter');
        $converter = $converter ?? $this->converter;

        // converts front matter
        if ($page->getFrontmatter()) {
            try {
                $variables = $converter->convertFrontmatter($page->getFrontmatter(), $format);
            } catch (RuntimeException $e) {
                throw new RuntimeException($e->getMessage(), file: $page->getFilePath(), line: $e->getLine());
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
